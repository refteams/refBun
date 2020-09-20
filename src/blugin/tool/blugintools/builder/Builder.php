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

namespace blugin\tool\blugintools\builder;

use blugin\tool\blugintools\BluginTools;
use blugin\tool\blugintools\builder\event\BuildCompleteEvent;
use blugin\tool\blugintools\builder\event\BuildPrepareEvent;
use blugin\tool\blugintools\builder\event\BuildStartEvent;
use blugin\tool\blugintools\loader\virion\Virion;
use blugin\tool\blugintools\loader\virion\VirionInjector;
use blugin\tool\blugintools\printer\Printer;
use blugin\tool\blugintools\processor\CodeSpliter;
use blugin\tool\blugintools\renamer\Renamer;
use blugin\tool\blugintools\traits\SingletonFactoryTrait;
use blugin\tool\blugintools\traverser\Traverser;
use blugin\tool\blugintools\traverser\TraverserPriority as Priority;
use blugin\tool\blugintools\visitor\CommentOptimizingVisitor;
use blugin\tool\blugintools\visitor\ImportForcingVisitor;
use blugin\tool\blugintools\visitor\ImportGroupingVisitor;
use blugin\tool\blugintools\visitor\ImportRemovingVisitor;
use blugin\tool\blugintools\visitor\ImportRenamingVisitor;
use blugin\tool\blugintools\visitor\ImportSortingVisitor;
use blugin\tool\blugintools\visitor\LocalVariableRenamingVisitor;
use blugin\tool\blugintools\visitor\PrivateConstRenamingVisitor;
use blugin\tool\blugintools\visitor\PrivateMethodRenamingVisitor;
use blugin\tool\blugintools\visitor\PrivatePropertyRenamingVisitor;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use pocketmine\command\PluginCommand;
use pocketmine\utils\Config;

class Builder{
    use SingletonFactoryTrait;

    public const OPTION_FILE = ".buildoption.yml";

    public const DIR_PREPARE = "prepare";
    public const DIR_BUILDED = "builded";

    /** @var Parser */
    protected static $parser = null;

    /** @var mixed[] */
    private $baseOption = [];

    /** @var string */
    private $printerMode = Printer::PRINTER_STANDARD;

    public function prepare(){
        Renamer::registerDefaults();
        Printer::registerDefaults();
        Traverser::registerDefaults();
        self::$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }

    public function init(){
        $this->baseOption = BluginTools::getInstance()->getConfig()->getAll();

        $command = BluginTools::getInstance()->getCommand("bluginbuilder");
        if($command instanceof PluginCommand){
            $command->setExecutor(new PluginBuildExecutor());
        }
    }

    /** @param mixed[] $metadata */
    public function buildPhar(string $sourceDir, string $pharPath, string $namespace, array $metadata = []) : void{
        $sourceDir = BluginTools::cleanDirName($sourceDir);
        $pharPath = BluginTools::cleanPath($pharPath);
        //Remove the existing PHAR file
        if(file_exists($pharPath)){
            try{
                \Phar::unlinkArchive($pharPath);
            }catch(\Exception $e){
                unlink($pharPath);
            }
        }

        $prepareDir = BluginTools::loadDir(self::DIR_PREPARE, true);
        $buildDir = BluginTools::loadDir(self::DIR_BUILDED, true);

        //Prepare to copy files for build
        $option = $this->loadOption($sourceDir);
        $prepareEvent = new BuildPrepareEvent($this, $sourceDir, $option);
        foreach(BluginTools::readDirectory($sourceDir, true) as $path){
            if($option->getNested("build.include-minimal", true)){
                $innerPath = substr($path, strlen($sourceDir));
                if($innerPath !== "plugin.yml" && strpos($innerPath, "src/") !== 0 && strpos($innerPath, "resources/") !== 0)
                    continue;
            }
            $prepareEvent->addFile($path, substr_replace($path, $prepareDir, 0, strlen($sourceDir)));
        }
        $prepareEvent->call();
        foreach($prepareEvent->getFiles() as $path => $newPath){
            $newDir = dirname($newPath);
            if(!file_exists($newDir)){
                mkdir($newDir, 0777, true);
            }

            if(is_file($path)){
                copy($path, $newPath);
            }
        }

        //Infect virions by '.poggit.yml' or option
        VirionInjector::injectAll($prepareDir, $namespace, Virion::getVirionOptions($sourceDir));

        //Build with various options
        (new BuildStartEvent($this, $sourceDir, $option))->call();
        foreach(BluginTools::readDirectory($prepareDir, true) as $path){
            if(substr($path, strlen($prepareDir)) === self::OPTION_FILE) //skip option file
                continue;

            $newPath = substr_replace($path, $buildDir, 0, strlen($prepareDir));
            $newDir = dirname($newPath);
            if(!file_exists($newDir)){
                mkdir($newDir, 0777, true);
            }

            if(preg_match("/([a-zA-Z0-9]*)\.php$/", $path, $matchs)){
                try{
                    $originStmts = self::$parser->parse(file_get_contents($path));
                    $originStmts = Traverser::get(Priority::BEFORE_SPLIT)->traverse($originStmts);

                    $files = [$matchs[1] => $originStmts];
                    if($option->getNested("preprocessing.spliting", true)){
                        $files = CodeSpliter::splitNodes($originStmts, $matchs[1]);
                    }

                    foreach($files as $filename => $stmts){
                        foreach(Priority::ALL as $priority){
                            $stmts = Traverser::get($priority)->traverse($stmts);
                        }
                        file_put_contents($newDir . DIRECTORY_SEPARATOR . $filename . ".php", Printer::getClone($this->printerMode)->print($stmts));
                    }
                }catch(\Error $e){
                    echo 'Parse Error: ', $e->getMessage();
                }
            }else{
                copy($path, $newPath);
            }
        }

        //Build the plugin with .phar file
        $phar = new \Phar($pharPath);
        $phar->setSignatureAlgorithm(\Phar::SHA1);
        if(!$option->getNested("build.skip-metadata", true)){
            $phar->setMetadata($metadata);
        }
        if(!$option->getNested("build.skip-stub", true)){
            $phar->setStub('<?php echo "PocketMine-MP plugin ' . "{$metadata["name"]}_v{$metadata["version"]}\nThis file has been generated using BluginBuilder at " . date("r") . '\n----------------\n";if(extension_loaded("phar")){$phar = new \Phar(__FILE__);foreach($phar->getMetadata() as $key => $value){echo ucfirst($key).": ".(is_array($value) ? implode(", ", $value):$value)."\n";}} __HALT_COMPILER();');
        }else{
            $phar->setStub("<?php __HALT_COMPILER();");
        }
        $phar->startBuffering();
        $phar->buildFromDirectory($buildDir);
        if(\Phar::canCompress(\Phar::GZ)){
            $phar->compressFiles(\Phar::GZ);
        }
        $phar->stopBuffering();
        (new BuildCompleteEvent($this, $sourceDir, $option))->call();
    }

    public function loadOption(string $dir) : Config{
        if(is_file($tempFile = BluginTools::loadDir() . self::OPTION_FILE)){
            unlink($tempFile);
        }
        if(is_file($optionFile = $dir . self::OPTION_FILE)){
            copy($optionFile, $tempFile);
        }
        $option = new Config($tempFile, Config::DETECT, $this->baseOption);

        //Remove old visitors of traserver
        foreach(Traverser::getAll() as $traverser){
            $traverser->removeVisitors();
        }

        //Load pre-processing settings
        if($option->getNested("preprocessing.comment-optimizing", true)){
            Traverser::registerVisitor(Priority::NORMAL, new CommentOptimizingVisitor());
        }

        //Load renaming mode settings
        foreach([
            "local-variable" => LocalVariableRenamingVisitor::class,
            "private-property" => PrivatePropertyRenamingVisitor::class,
            "private-method" => PrivateMethodRenamingVisitor::class,
            "private-const" => PrivateConstRenamingVisitor::class
        ] as $key => $class){
            $renamer = Renamer::getClone($option->getNested("preprocessing.renaming.$key", "serial"));
            if($renamer !== null){
                Traverser::registerVisitor(Priority::NORMAL, new $class($renamer));
            }
        }

        //Load import processing mode settings
        $mode = $option->getNested("preprocessing.importing.renaming", "serial");
        if($mode === "resolve"){
            Traverser::registerVisitor(Priority::HIGH, new ImportRemovingVisitor());
        }else{
            if(($renamer = Renamer::getClone($mode)) !== null){
                Traverser::registerVisitor(Priority::HIGH, new ImportRenamingVisitor($renamer));
            }
            if($option->getNested("preprocessing.importing.forcing", true)){
                Traverser::registerVisitor(Priority::NORMAL, new ImportForcingVisitor());
            }
            if($option->getNested("preprocessing.importing.grouping", true)){
                Traverser::registerVisitor(Priority::HIGHEST, new ImportGroupingVisitor());
            }
            if($option->getNested("preprocessing.importing.sorting", true)){
                Traverser::registerVisitor(Priority::HIGHEST, new ImportSortingVisitor());
            }
        }

        //Load build settings
        $this->printerMode = $option->getNested("build.print-format");

        return $option;
    }
}
