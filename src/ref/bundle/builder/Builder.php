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

namespace ref\bundle\builder;

use ref\bundle\refBun;
use ref\bundle\builder\event\BuildCompleteEvent;
use ref\bundle\builder\event\BuildPrepareEvent;
use ref\bundle\builder\event\BuildStartEvent;
use ref\bundle\loader\virion\Virion;
use ref\bundle\loader\virion\VirionInjector;
use ref\bundle\printer\Printer;
use ref\bundle\processor\CodeSplitter;
use ref\bundle\renamer\Renamer;
use ref\bundle\traits\SingletonFactoryTrait;
use ref\bundle\traverser\Traverser;
use ref\bundle\traverser\TraverserPriority as Priority;
use ref\bundle\visitor\CommentOptimizingVisitor;
use ref\bundle\visitor\ConstructorPromotionRemoveVisitor;
use ref\bundle\visitor\ImportForcingVisitor;
use ref\bundle\visitor\ImportGroupingVisitor;
use ref\bundle\visitor\ImportRemovingVisitor;
use ref\bundle\visitor\ImportRenamingVisitor;
use ref\bundle\visitor\ImportSortingVisitor;
use ref\bundle\visitor\LocalVariableRenamingVisitor;
use ref\bundle\visitor\PrivateConstRenamingVisitor;
use ref\bundle\visitor\PrivateMethodRenamingVisitor;
use ref\bundle\visitor\PrivatePropertyRenamingVisitor;
use Error;
use Exception;
use Phar;
use PhpParser\Node\Stmt;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use pocketmine\command\PluginCommand;
use pocketmine\utils\Config;

use function copy;
use function date;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_file;
use function mkdir;
use function preg_match;
use function strlen;
use function strpos;
use function substr;
use function substr_replace;
use function unlink;

class Builder{
    use SingletonFactoryTrait;

    public const OPTION_FILE = ".buildoption.yml";

    public const DIR_PREPARE = "prepare";
    public const DIR_RESULT = "result";

    protected static ?Parser $parser = null;

    private array $baseOption = [];

    public function prepare(){
        Renamer::registerDefaults();
        Printer::registerDefaults();
        Traverser::registerDefaults();
    }

    public function init(){
        $this->baseOption = refBun::getInstance()->getConfig()->getAll();

        $command = refBun::getInstance()->getCommand("refbundle");
        if($command instanceof PluginCommand){
            $command->setExecutor(new PluginBuildExecutor());
        }
    }

    public function buildPhar(string $sourceDir, string $pharPath, string $namespace, array $metadata = []) : void{
        $sourceDir = refBun::cleanDirName($sourceDir);
        $pharPath = refBun::cleanPath($pharPath);
        //Remove the existing PHAR file
        if(file_exists($pharPath)){
            try{
                Phar::unlinkArchive($pharPath);
            }catch(Exception $e){
                unlink($pharPath);
            }
        }

        $prepareDir = refBun::loadDir(self::DIR_PREPARE, true);
        $buildDir = refBun::loadDir(self::DIR_RESULT, true);

        //Prepare to copy files for build
        $option = $this->loadOption($sourceDir);
        $prepareEvent = new BuildPrepareEvent($this, $sourceDir, $pharPath, $option);
        foreach(refBun::readDirectory($sourceDir, true) as $path){
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
        (new BuildStartEvent($this, $sourceDir, $pharPath, $option))->call();
        $printers = $this->loadPrintersFromOption($option);

        foreach(refBun::readDirectory($prepareDir, true) as $path){
            if(substr($path, strlen($prepareDir)) === self::OPTION_FILE) //skip option file
                continue;

            $newPath = substr_replace($path, $buildDir, 0, strlen($prepareDir));
            $newDir = dirname($newPath);
            if(!file_exists($newDir)){
                mkdir($newDir, 0777, true);
            }

            if(preg_match("/([a-zA-Z0-9]*)\.php$/", $path, $matches)){
                try{
                    $originStmts = self::parse(file_get_contents($path));
                    $originStmts = Traverser::get(Priority::BEFORE_SPLIT)->traverse($originStmts);

                    $files = [$matches[1] => $originStmts];
                    if($option->getNested("preprocessing.splitting", true)){
                        $files = CodeSplitter::splitNodes($originStmts, $matches[1]);
                    }

                    foreach($files as $filename => $stmts){
                        foreach(Priority::DEFAULT as $priority){
                            $stmts = Traverser::get($priority)->traverse($stmts);
                        }

                        $contents = null;
                        foreach($printers as $printer){
                            $contents = $printer->print($contents ?? $stmts);
                        }
                        file_put_contents($newDir . DIRECTORY_SEPARATOR . $filename . ".php", $contents);
                    }
                }catch(Error $e){
                    echo 'Parse Error: ', $e->getMessage();
                }
            }else{
                copy($path, $newPath);
            }
        }

        //Build the plugin with .phar file
        $phar = new Phar($pharPath);
        $phar->setSignatureAlgorithm(Phar::SHA1);
        if(!$option->getNested("build.skip-metadata", true)){
            $phar->setMetadata($metadata);
        }
        if(!$option->getNested("build.skip-stub", true)){
            $phar->setStub('<?php echo "PocketMine-MP plugin ' . "{$metadata["name"]}_v{$metadata["version"]}\nThis file has been generated using BluginBuilder at " . date("r") . '\n----------------\n";if(extension_loaded("phar")){$phar = new Phar(__FILE__);foreach($phar->getMetadata() as $key => $value){echo ucfirst($key).": ".(is_array($value) ? implode(", ", $value):$value)."\n";}} __HALT_COMPILER();');
        }else{
            $phar->setStub("<?php __HALT_COMPILER();");
        }
        $phar->startBuffering();
        $phar->buildFromDirectory($buildDir);
        if(Phar::canCompress(Phar::GZ)){
            $phar->compressFiles(Phar::GZ);
        }
        $phar->stopBuffering();
        (new BuildCompleteEvent($this, $sourceDir, $pharPath, $option))->call();
    }

    public function buildScript(string $sourcePath, string $phpPath, array $metadata = []) : void{
        $sourcePath = refBun::cleanPath($sourcePath);
        $phpPath = refBun::cleanPath($phpPath);
        //Remove the existing PHP file
        if(is_file($phpPath)){
            unlink($phpPath);
        }

        //Prepare to copy files for build
        $option = $this->loadOption($sourceDir = refBun::cleanDirName(dirname($sourcePath)));

        (new BuildStartEvent($this, $sourceDir, $phpPath, $option))->call();
        $printers = $this->loadPrintersFromOption($option);

        try{
            $stmts = self::parse(file_get_contents($sourcePath));
            foreach(Priority::DEFAULT as $priority){
                $stmts = Traverser::get($priority)->traverse($stmts);
            }

            $contents = null;
            foreach($printers as $printer){
                $contents = $printer->print($contents ?? $stmts);
            }
            file_put_contents($phpPath, $contents);
            (new BuildCompleteEvent($this, $sourceDir, $phpPath, $option))->call();
        }catch(Error $e){
            echo 'Parse Error: ', $e->getMessage();
        }
    }

    public function loadOption(string $dir) : Config{
        if(is_file($tempFile = refBun::loadDir() . self::OPTION_FILE)){
            unlink($tempFile);
        }
        if(is_file($optionFile = $dir . self::OPTION_FILE)){
            copy($optionFile, $tempFile);
        }
        $option = new Config($tempFile, Config::DETECT, $this->baseOption);

        //Remove old visitors of traverser
        foreach(Traverser::getAll() as $traverser){
            $traverser->removeVisitors();
        }
        Traverser::registerVisitor(Priority::BEFORE_SPLIT, new ConstructorPromotionRemoveVisitor());

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
            $renamer = Renamer::getClone((string) $option->getNested("preprocessing.renaming.$key", "serial"));
            if($renamer !== null){
                Traverser::registerVisitor(Priority::NORMAL, new $class($renamer));
            }
        }

        //Load import processing mode settings
        $mode = (string) $option->getNested("preprocessing.importing.renaming", "serial");
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

        return $option;
    }

    /** @return Printer[] */
    public function loadPrintersFromOption(Config $option) : array{
        $printers = [];
        foreach($option->getNested("build.print-format") as $printerName){
            $printer = Printer::getClone((string) $printerName);
            if($printer === null)
                throw new Error("$printerName is invalid printer mode");

            $printers [] = $printer;
        }
        if(empty($printers))
            $printers[] = Printer::getClone();

        return $printers;
    }

    public static function getParser() : Parser{
        static $parser = null;
        if(empty($parser)){
            $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        }

        return $parser;
    }

    /** @return Stmt[]|null */
    public static function parse(string $code) : ?array{
        return self::getParser()->parse($code);
    }
}
