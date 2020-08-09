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
use blugin\tool\builder\printer\OptimizePrinter;
use blugin\tool\builder\printer\PrettyPrinter;
use blugin\tool\builder\printer\ShortenPrinter;
use blugin\tool\builder\visitor\CommentOptimizingVisitor;
use blugin\tool\builder\visitor\ImportForcingVisitor;
use blugin\tool\builder\visitor\ImportGroupingVisitor;
use blugin\tool\builder\visitor\ImportRemovingVisitor;
use blugin\tool\builder\visitor\ImportRenamingVisitor;
use blugin\tool\builder\visitor\LocalVariableRenamingVisitor;
use blugin\tool\builder\visitor\PrivateConstRenamingVisitor;
use blugin\tool\builder\visitor\PrivateMethodRenamingVisitor;
use blugin\tool\builder\visitor\PrivatePropertyRenamingVisitor;
use blugin\tool\builder\visitor\renamer\MD5Renamer;
use blugin\tool\builder\visitor\renamer\ProtectRenamer;
use blugin\tool\builder\visitor\renamer\Renamer;
use blugin\tool\builder\visitor\renamer\SerialRenamer;
use blugin\tool\builder\visitor\renamer\ShortenRenamer;
use blugin\tool\builder\visitor\renamer\SpaceRenamer;
use PhpParser\NodeTraverser;
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

    public const RENAMER_PROECT = "protect";
    public const RENAMER_SHORTEN = "shorten";
    public const RENAMER_SERIAL = "serial";
    public const RENAMER_SPACE = "space";
    public const RENAMER_MD5 = "md5";
    /** @var Renamer[] renamer tag -> renamer instance */
    private $renamers = [];

    public const PRINTER_PRETTY = "pretty";
    public const PRINTER_SHORTEN = "shorten";
    /** @var IPrinter[] printer tag -> printer instance */
    private $printers = [];

    /** @var NodeTraverser */
    private $traverser;
    /** @var IPrinter */
    private $printer;

    public function onLoad(){
        self::$instance = $this;

        $this->renamers[self::RENAMER_PROECT] = new ProtectRenamer();
        $this->renamers[self::RENAMER_SHORTEN] = new ShortenRenamer();
        $this->renamers[self::RENAMER_SERIAL] = new SerialRenamer();
        $this->renamers[self::RENAMER_SPACE] = new SpaceRenamer();
        $this->renamers[self::RENAMER_MD5] = new MD5Renamer();

        $this->printers[self::PRINTER_PRETTY] = new PrettyPrinter();
        $this->printers[self::PRINTER_SHORTEN] = new ShortenPrinter();

        $config = $this->getConfig();
        //Load pre-processing settings
        $this->traverser = new NodeTraverser();

        if($config->getNested("preprocessing.comment-optimizing", true)){
            $this->traverser->addVisitor(new CommentOptimizingVisitor());
        }

        //Load renaming mode settings
        foreach([
            "local-variable" => LocalVariableRenamingVisitor::class,
            "private-property" => PrivatePropertyRenamingVisitor::class,
            "private-method" => PrivateMethodRenamingVisitor::class,
            "private-const" => PrivateConstRenamingVisitor::class
        ] as $key => $class){
            if(isset($this->renamers[$mode = $config->getNested("preprocessing.renaming.$key", "serial")])){
                $this->traverser->addVisitor(new $class(clone $this->renamers[$mode]));
            }
        }

        //Load import processing mode settings
        if($config->getNested("preprocessing.importing.forcing", true)){
            $this->traverser->addVisitor(new ImportForcingVisitor());
        }
        if($config->getNested("preprocessing.importing.grouping", true)){
            $this->traverser->addVisitor(new ImportGroupingVisitor());
        }
        $mode = $config->getNested("preprocessing.importing.renaming", "serial");
        if(isset($this->renamers[$mode])){
            $this->traverser->addVisitor(new ImportRenamingVisitor(clone $this->renamers[$mode]));
        }elseif($mode === "resolve"){
            $this->traverser->addVisitor(new ImportRemovingVisitor());
        }

        //Load build settings
        $printerMode = $config->getNested("build.print-format");
        $printerMode = isset($this->printers[$printerMode]) ? $printerMode : "shorten";
        $this->printer = $this->printers[$printerMode];
    }

    public function onEnable(){
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
            if(!file_exists(dirname($out))){
                mkdir(dirname($out), 0777, true);
            }

            if(preg_match("/\.php$/", $path)){
                try{
                    $contents = file_get_contents($fileInfo->getPathName());
                    $stmts = $parser->parse($contents);
                    $stmts = $this->traverser->traverse($stmts);
                    $contents = $this->printer->print($stmts);
                    if($config->getNested("preprocessing.minor-optimizating", true)){
                        $contents = (new OptimizePrinter())->print($parser->parse($contents));
                    }
                    file_put_contents($out, $contents);
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

    /** @return NodeTraverser */
    public function getTraverser() : NodeTraverser{
        return $this->traverser;
    }

    /** @return IPrinter */
    public function getPrinter() : IPrinter{
        return $this->printer;
    }
}
