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
use blugin\tool\blugintools\printer\Printer;
use blugin\tool\blugintools\processor\CodeSpliter;
use blugin\tool\blugintools\renamer\Renamer;
use blugin\tool\blugintools\traverser\AdvancedTraverser;
use blugin\tool\blugintools\builder\TraverserPriority as Priority;
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
use blugin\tool\blugintools\loader\virion\VirionInjector;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use pocketmine\command\PluginCommand;
use pocketmine\utils\Config;

class AdvancedBuilder{
    public const OPTION_FILE = ".advancedbuilder.yml";

    public const DIR_PREPARE = "prepare";
    public const DIR_BUILDED = "builded";

    /** @var AdvancedBuilder */
    private static $instance = null;

    public static function getInstance() : AdvancedBuilder{
        if(self::$instance === null){
            self::$instance = new AdvancedBuilder();
        }
        return self::$instance;
    }

    /** @var mixed[] */
    private $baseOption = [];

    /** @var Renamer[] renamer tag -> renamer instance */
    private $renamers = [];

    /** @var Printer[] printer tag -> printer instance */
    private $printers = [];

    /** @var AdvancedTraverser[] traverser priority => AdvancedeTraverser */
    private $traversers;

    /** @var string */
    private $printerMode = Printer::PRINTER_STANDARD;

    private function __construct(){
        Renamer::registerDefaults($this);
        Printer::registerDefaults($this);

        //Load pre-processing settings
        foreach(Priority::ALL as $priority){
            $this->traversers[$priority] = new AdvancedTraverser();
        }
    }

    public function init(){
        $this->baseOption = BluginTools::getInstance()->getConfig()->getAll();

        $command = BluginTools::getInstance()->getCommand("bluginbuilder");
        if($command instanceof PluginCommand){
            $command->setExecutor(new BuildCommandExecutor($this));
        }
    }

    /** @param mixed[] $metadata */
    public function buildPhar(string $sourceDir, string $pharPath, array $metadata) : void{
        $sourceDir = BluginTools::cleanDirName($sourceDir);
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
        if(file_exists($poggitYmlFile = $sourceDir . ".poggit.yml")){
            $poggitYml = yaml_parse(file_get_contents($poggitYmlFile));
            if(is_array($poggitYml) && isset($poggitYml["projects"])){
                foreach($poggitYml["projects"] as $projectOption){
                    if(empty($projectOption["path"])){
                        $option->setNested("virions", $projectOption["libs"] ?? []);
                        break;
                    }
                }
            }
        }
        VirionInjector::injectAll($prepareDir, $option);

        //Build with various options
        (new BuildStartEvent($this, $sourceDir, $option))->call();
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
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
                    $originStmts = $parser->parse(file_get_contents($path));
                    $originStmts = $this->traversers[Priority::BEFORE_SPLIT]->traverse($originStmts);

                    $files = [$matchs[1] => $originStmts];
                    if($option->getNested("preprocessing.spliting", true)){
                        $files = CodeSpliter::splitNodes($originStmts, $matchs[1]);
                    }

                    foreach($files as $filename => $stmts){
                        foreach(Priority::DEFAULTS as $priority){
                            $stmts = $this->traversers[$priority]->traverse($stmts);
                        }
                        file_put_contents($newDir . DIRECTORY_SEPARATOR . $filename . ".php", $this->getPrinter($this->printerMode)->print($stmts));
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

    public function loadOption(string $dir, int $type = Config::DETECT) : Config{
        if(!is_file($file = $dir . self::OPTION_FILE)){
            $file = BluginTools::loadDir(self::DIR_PREPARE) . self::OPTION_FILE;
        }
        $option = new Config($file, $type, $this->baseOption);

        //Remove old visitors of traserver
        foreach(Priority::ALL as $priority){
            $this->traversers[$priority]->removeVisitors();
        }

        //Load pre-processing settings
        if($option->getNested("preprocessing.comment-optimizing", true)){
            $this->registerVisitor(Priority::NORMAL, new CommentOptimizingVisitor());
        }

        //Load renaming mode settings
        foreach([
            "local-variable" => LocalVariableRenamingVisitor::class,
            "private-property" => PrivatePropertyRenamingVisitor::class,
            "private-method" => PrivateMethodRenamingVisitor::class,
            "private-const" => PrivateConstRenamingVisitor::class
        ] as $key => $class){
            if(isset($this->renamers[$mode = $option->getNested("preprocessing.renaming.$key", "serial")])){
                $this->registerVisitor(Priority::NORMAL, new $class(clone $this->renamers[$mode]));
            }
        }

        //Load import processing mode settings
        $mode = $option->getNested("preprocessing.importing.renaming", "serial");
        if($mode === "resolve"){
            $this->registerVisitor(Priority::HIGH, new ImportRemovingVisitor());
        }else{
            if(isset($this->renamers[$mode])){
                $this->registerVisitor(Priority::HIGH, new ImportRenamingVisitor(clone $this->renamers[$mode]));
            }
            if($option->getNested("preprocessing.importing.forcing", true)){
                $this->registerVisitor(Priority::NORMAL, new ImportForcingVisitor());
            }
            if($option->getNested("preprocessing.importing.grouping", true)){
                $this->registerVisitor(Priority::HIGHEST, new ImportGroupingVisitor());
            }
            if($option->getNested("preprocessing.importing.sorting", true)){
                $this->registerVisitor(Priority::HIGHEST, new ImportSortingVisitor());
            }
        }

        //Load build settings
        $this->printerMode = $option->getNested("build.print-format");

        return $option;
    }

    /** @return AdvancedTraverser[] */
    public function getTraversers() : array{
        return $this->traversers;
    }

    public function getTraverser(int $priority = Priority::NORMAL) : ?AdvancedTraverser{
        return $this->traversers[$priority] ?? null;
    }

    public function registerVisitor(int $priority, NodeVisitorAbstract $visitor) : bool{
        $traverser = $this->getTraverser($priority);
        if($traverser === null)
            return false;

        $traverser->addVisitor($visitor);
        return true;
    }

    public function getPrinter(string $mode = Printer::PRINTER_STANDARD) : Printer{
        return clone($this->printers[$mode] ?? $this->printers[Printer::PRINTER_STANDARD]);
    }

    public function registerPrinter(string $mode, Printer $printer) : void{
        $this->printers[$mode] = $printer;
    }

    /** @return Renamer[] */
    public function getRenamers() : array{
        return $this->renamers;
    }

    public function getRenamer(string $mode) : ?Renamer{
        return isset($this->renamers[$mode]) ? clone $this->renamers[$mode] : null;
    }

    public function registerRenamer(string $mode, Renamer $renamer) : void{
        $this->renamers[$mode] = $renamer;
    }
}
