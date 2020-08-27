<?php

/*
 *
 *  ____  _             _         _____
 * | __ )| |_   _  __ _(_)_ __   |_   _|__  __ _ _ __ ___
 * |  _ \| | | | |/ _` | | '_ \    | |/ _ \/ _` | '_ ` _ \
 * | |_) | | |_| | (_| | | | | |   | |  __/ (_| | | | | | |
 * |____/|_|\__,_|\__, |_|_| |_|   |_|\___|\__,_|_| |_| |_|
 *                |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  Blugin team
 * @link    https://github.com/Blugin
 * @license https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace blugin\tool\builder;

use blugin\tool\builder\printer\IPrinter;
use blugin\tool\builder\printer\ShortenPrinter;
use blugin\tool\builder\printer\StandardPrinter;
use blugin\tool\builder\renamer\MD5Renamer;
use blugin\tool\builder\renamer\Renamer;
use blugin\tool\builder\renamer\SerialRenamer;
use blugin\tool\builder\renamer\ShortenRenamer;
use blugin\tool\builder\renamer\SpaceRenamer;
use blugin\tool\builder\TraverserPriority as Priority;
use blugin\tool\builder\visitor\CommentOptimizingVisitor;
use blugin\tool\builder\visitor\ImportForcingVisitor;
use blugin\tool\builder\visitor\ImportGroupingVisitor;
use blugin\tool\builder\visitor\ImportRemovingVisitor;
use blugin\tool\builder\visitor\ImportRenamingVisitor;
use blugin\tool\builder\visitor\ImportSortingVisitor;
use blugin\tool\builder\visitor\LocalVariableRenamingVisitor;
use blugin\tool\builder\visitor\PrivateConstRenamingVisitor;
use blugin\tool\builder\visitor\PrivateMethodRenamingVisitor;
use blugin\tool\builder\visitor\PrivatePropertyRenamingVisitor;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use pocketmine\command\PluginCommand;
use pocketmine\plugin\PluginBase;

class BluginBuilder extends PluginBase{
    /** @var BluginBuilder */
    private static $instance;

    /** @return BluginBuilder */
    public static function getInstance() : BluginBuilder{
        return self::$instance;
    }

    public const RENAMER_SHORTEN = "shorten";
    public const RENAMER_SERIAL = "serial";
    public const RENAMER_SPACE = "space";
    public const RENAMER_MD5 = "md5";
    /** @var Renamer[] renamer tag -> renamer instance */
    private $renamers = [];

    public const PRINTER_STANDARD = "standard";
    public const PRINTER_SHORTEN = "shorten";
    /** @var IPrinter[] printer tag -> printer instance */
    private $printers = [];

    /** @var NodeTraverser[] traverser priority => NodeTraverser */
    private $traversers;

    /** @var string */
    private $printerMode = self::PRINTER_STANDARD;

    public function onLoad(){
        self::$instance = $this;

        $this->registerRenamer(self::RENAMER_SHORTEN, new ShortenRenamer());
        $this->registerRenamer(self::RENAMER_SERIAL, new SerialRenamer());
        $this->registerRenamer(self::RENAMER_SPACE, new SpaceRenamer());
        $this->registerRenamer(self::RENAMER_MD5, new MD5Renamer());

        $this->registerPrinter(self::PRINTER_STANDARD, new StandardPrinter());
        $this->registerPrinter(self::PRINTER_SHORTEN, new ShortenPrinter());

        //Load pre-processing settings
        foreach(Priority::ALL as $priority){
            $this->traversers[$priority] = new NodeTraverser();
        }
    }

    public function onEnable(){
        $config = $this->getConfig();

        if($config->getNested("preprocessing.comment-optimizing", true)){
            $this->registerVisitor(Priority::NORMAL, new CommentOptimizingVisitor());
        }

        //Load renaming mode settings
        foreach([
            "local-variable" => LocalVariableRenamingVisitor::class,
            "private-property" => PrivatePropertyRenamingVisitor::class,
            "private-method" => PrivateMethodRenamingVisitor::class,
            "private-const" => PrivateConstRenamingVisitor::class
        ] as $key => $class){
            if(isset($this->renamers[$mode = $config->getNested("preprocessing.renaming.$key", "serial")])){
                $this->registerVisitor(Priority::NORMAL, new $class(clone $this->renamers[$mode]));
            }
        }

        //Load import processing mode settings
        $mode = $config->getNested("preprocessing.importing.renaming", "serial");
        if($mode === "resolve"){
            $this->registerVisitor(Priority::HIGH, new ImportRemovingVisitor());
        }else{
            if(isset($this->renamers[$mode])){
                $this->registerVisitor(Priority::HIGH, new ImportRenamingVisitor(clone $this->renamers[$mode]));
            }
            if($config->getNested("preprocessing.importing.forcing", true)){
                $this->registerVisitor(Priority::NORMAL, new ImportForcingVisitor());
            }
            if($config->getNested("preprocessing.importing.grouping", true)){
                $this->registerVisitor(Priority::HIGHEST, new ImportGroupingVisitor());
            }
            if($config->getNested("preprocessing.importing.sorting", true)){
                $this->registerVisitor(Priority::HIGHEST, new ImportSortingVisitor());
            }
        }

        //Load build settings
        $this->printerMode = $config->getNested("build.print-format");

        $command = $this->getCommand("bluginbuilder");
        if($command instanceof PluginCommand){
            $command->setExecutor(new BuildCommandExecutor($this));
        }
    }

    /**
     * @param PluginBase $plugin
     *
     * @throws \ReflectionException
     */
    public function buildPlugin(PluginBase $plugin) : void{
        $reflection = new \ReflectionClass(PluginBase::class);
        $fileProperty = $reflection->getProperty("file");
        $fileProperty->setAccessible(true);
        $sourcePath = rtrim(realpath($fileProperty->getValue($plugin)), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $pharPath = "{$this->getDataFolder()}{$plugin->getName()}_v{$plugin->getDescription()->getVersion()}.phar";

        $description = $plugin->getDescription();
        $metadata = [
            "name" => $description->getName(),
            "version" => $description->getVersion(),
            "main" => $description->getMain(),
            "api" => $description->getCompatibleApis(),
            "depend" => $description->getDepend(),
            "description" => $description->getDescription(),
            "authors" => $description->getAuthors(),
            "website" => $description->getWebsite(),
            "creationDate" => time()
        ];
        $this->buildPhar($sourcePath, $pharPath, $metadata);
    }

    /**
     * @param string $pharPath
     * @param string $filePath
     * @param array  $metadata
     */
    public function buildPhar(string $filePath, string $pharPath, array $metadata) : void{
        //Remove the existing PHAR file
        if(file_exists($pharPath)){
            try{
                \Phar::unlinkArchive($pharPath);
            }catch(\Exception $e){
                unlink($pharPath);
            }
        }

        //Remove the existing build folder and remake
        $buildPath = "{$this->getDataFolder()}build/";
        $this->clearDirectory($buildPath);

        //Pre-build processing execution
        $config = $this->getConfig();
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($filePath, \FilesystemIterator::SKIP_DOTS)) as $path => $fileInfo){
            if($config->getNested("build.include-minimal", true)){
                $inPath = substr($path, strlen($filePath));
                if($inPath !== "plugin.yml" && strpos($inPath, "src\\") !== 0 && strpos($inPath, "resources\\") !== 0)
                    continue;
            }

            $out = substr_replace($path, $buildPath, 0, strlen($filePath));
            $outDir = dirname($out);
            if(!file_exists($outDir)){
                mkdir($outDir, 0777, true);
            }

            if(preg_match("/([a-zA-Z0-9]*)\.php$/", $path, $matchs)){
                try{
                    $contents = file_get_contents($fileInfo->getPathName());
                    $originalStmts = $parser->parse($contents);
                    $originalStmts = $this->traversers[Priority::BEFORE_SPLIT]->traverse($originalStmts);

                    $files = [$matchs[1] => $originalStmts];
                    if($config->getNested("preprocessing.spliting", true)){
                        $files = CodeSpliter::splitNodes($originalStmts, $matchs[1]);
                    }

                    foreach($files as $filename => $stmts){
                        foreach(Priority::DEFAULTS as $priority){
                            $stmts = $this->traversers[$priority]->traverse($stmts);
                        }
                        file_put_contents($outDir . DIRECTORY_SEPARATOR . $filename . ".php", $this->getPrinter()->print($stmts));
                    }
                }catch(\Error $e){
                    echo 'Parse Error: ', $e->getMessage();
                }
            }else{
                copy($path, $out);
            }
        }

        //Build the plugin with .phar file
        $phar = new \Phar($pharPath);
        $phar->setSignatureAlgorithm(\Phar::SHA1);
        if(!$config->getNested("build.skip-metadata", true)){
            $phar->setMetadata($metadata);
        }
        if(!$config->getNested("build.skip-stub", true)){
            $phar->setStub('<?php echo "PocketMine-MP plugin ' . "{$metadata["name"]}_v{$metadata["version"]}\nThis file has been generated using BluginBuilder at " . date("r") . '\n----------------\n";if(extension_loaded("phar")){$phar = new \Phar(__FILE__);foreach($phar->getMetadata() as $key => $value){echo ucfirst($key).": ".(is_array($value) ? implode(", ", $value):$value)."\n";}} __HALT_COMPILER();');
        }else{
            $phar->setStub("<?php __HALT_COMPILER();");
        }
        $phar->startBuffering();
        $phar->buildFromDirectory($buildPath);
        if(\Phar::canCompress(\Phar::GZ)){
            $phar->compressFiles(\Phar::GZ);
        }
        $phar->stopBuffering();
    }

    /**
     * @Override for multilingual support of the config file
     * @url https://github.com/Blugin/libTranslator-PMMP/blob/master/src/blugin/lib/translator/MultilingualConfigTrait.php
     * @return bool
     */
    public function saveDefaultConfig() : bool{
        $configFile = "{$this->getDataFolder()}config.yml";
        if(file_exists($configFile))
            return false;

        $resource = $this->getResource("locale/{$this->getServer()->getLanguage()->getLang()}.yml");
        if($resource === null){
            foreach($this->getResources() as $filePath => $info){
                if(preg_match('/^locale\/[a-zA-Z]{3}\.yml$/', $filePath)){
                    $resource = $this->getResource($filePath);
                    break;
                }
            }
        }
        if($resource === null)
            return false;

        $ret = stream_copy_to_stream($resource, $fp = fopen($configFile, "wb")) > 0;
        fclose($fp);
        fclose($resource);
        return $ret;
    }

    /**
     * @param string $directory
     *
     * @return bool
     */
    private function clearDirectory(string $directory) : bool{
        if(!file_exists($directory))
            return mkdir($directory, 0777, true);

        $files = array_diff(scandir($directory), [".", ".."]);
        foreach($files as $file){
            $path = "{$directory}/{$file}";
            if(is_dir($path)){
                $this->clearDirectory($path);
                rmdir($path);
            }else{
                unlink($path);
            }
        }
        return (count(scandir($directory)) == 2);
    }

    /** @return NodeTraverser[] */
    public function getTraversers() : array{
        return $this->traversers;
    }

    /**
     * @param int $priority
     *
     * @return NodeTraverser|null
     */
    public function getTraverser(int $priority = Priority::NORMAL) : ?NodeTraverser{
        return $this->traversers[$priority] ?? null;
    }

    /**
     * @param int                 $priority
     *
     * @param NodeVisitorAbstract $visitor
     *
     * @return bool
     */
    public function registerVisitor(int $priority, NodeVisitorAbstract $visitor) : bool{
        $traverser = $this->getTraverser($priority);
        if($traverser === null)
            return false;

        $traverser->addVisitor($visitor);
        return true;
    }

    /**
     * @param string|null $mode
     *
     * @return IPrinter
     */
    public function getPrinter(string $mode = null) : IPrinter{
        return clone($this->printers[$mode] ?? $this->printers[$this->printerMode]);
    }

    /**
     * @param string   $mode
     * @param IPrinter $printer
     */
    public function registerPrinter(string $mode, IPrinter $printer) : void{
        $this->printers[$mode] = $printer;
    }

    /**
     * @return Renamer[]
     */
    public function getRenamers() : array{
        return $this->renamers;
    }

    /**
     * @param string $mode
     *
     * @return Renamer|null
     */
    public function getRenamer(string $mode) : ?Renamer{
        return isset($this->renamers[$mode]) ? clone $this->renamers[$mode] : null;
    }

    /**
     * @param string  $mode
     * @param Renamer $renamer
     */
    public function registerRenamer(string $mode, Renamer $renamer) : void{
        $this->renamers[$mode] = $renamer;
    }
}
