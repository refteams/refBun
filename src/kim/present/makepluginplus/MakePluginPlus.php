<?php

/*
 *
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  PresentKim (debe3721@gmail.com)
 * @link    https://github.com/PresentKim
 * @license https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace kim\present\makepluginplus;

use FolderPluginLoader\FolderPluginLoader;
use kim\present\makepluginplus\util\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

class MakePluginPlus extends PluginBase{
    use SingletonTrait;

    /**
     * Called when the plugin is loaded, before calling onEnable()
     */
    public function onLoad() : void{
        self::setInstance($this);
    }

    /**
     * Called when the plugin is enabled
     */
    public function onEnable() : void{
        $dataFolder = $this->getDataFolder();
        if(!file_exists($dataFolder)){
            mkdir($dataFolder, 0777, true);
        }
        $this->saveDefaultConfig();
        $this->reloadConfig();

        $command = new PluginCommand("makepluginplus", $this, $this);
        $command->setPermission("makepluginplus.cmd");
        $command->setDescription("Build the plugin with optimizing");
        $command->setUsage("/makepluginplus <plugin name>");
        $command->setAliases(["build", "mpp"]);
        $this->getServer()->getCommandMap()->register("makepluginplus", $command);
    }

    /**
     * @param CommandSender $sender
     * @param Command       $command
     * @param string        $label
     * @param string[]      $args
     *
     * @return bool
     * @throws \ReflectionException
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if(!empty($args[0])){
            /** @var PluginBase[] $plugins */
            $plugins = [];
            $pluginManager = Server::getInstance()->getPluginManager();
            if($args[0] === "*"){
                foreach($pluginManager->getPlugins() as $pluginName => $plugin){
                    if($plugin->getPluginLoader() instanceof FolderPluginLoader){
                        $plugins[$plugin->getName()] = $plugin;
                    }
                }
            }else{
                foreach($args as $key => $pluginName){
                    $plugin = Utils::getPlugin($pluginName);
                    if($plugin === null){
                        $sender->sendMessage("{$pluginName} is invalid plugin name");
                    }elseif(!($plugin->getPluginLoader() instanceof FolderPluginLoader)){
                        $sender->sendMessage("{$plugin->getName()} is not in folder plugin");
                    }else{
                        $plugins[$plugin->getName()] = $plugin;
                    }
                }
            }
            $pluginCount = count($plugins);
            $sender->sendMessage("Start build the {$pluginCount} plugins");

            $reflection = new \ReflectionClass(PluginBase::class);
            $fileProperty = $reflection->getProperty("file");
            $fileProperty->setAccessible(true);
            if(!file_exists($dataFolder = $this->getDataFolder())){
                mkdir($dataFolder, 0777, true);
            }
            foreach($plugins as $pluginName => $plugin){
                $pluginVersion = $plugin->getDescription()->getVersion();
                $pharName = "{$pluginName}_v{$pluginVersion}.phar";
                $filePath = rtrim(str_replace("\\", "/", $fileProperty->getValue($plugin)), "/") . "/";
                $this->buildPhar($plugin, $filePath, "{$dataFolder}{$pharName}");
                $sender->sendMessage("{$pharName} has been created on {$dataFolder}");
            }
            $sender->sendMessage("Complete built the {$pluginCount} plugins");
            return true;
        }
        return false;
    }

    /**
     * @param PluginBase $plugin
     * @param string     $pharPath
     * @param string     $filePath
     */
    public function buildPhar(PluginBase $plugin, string $filePath, string $pharPath) : void{
        $setting = $this->getConfig()->getAll();
        $description = $plugin->getDescription();
        if(file_exists($pharPath)){
            try{
                \Phar::unlinkArchive($pharPath);
            }catch(\Exception $e){
                unlink($pharPath);
            }
        }
        $phar = new \Phar($pharPath);
        $phar->setSignatureAlgorithm(\Phar::SHA1);
        if(!$setting["skip-metadata"]){
            $phar->setMetadata([
                "name" => $description->getName(),
                "version" => $description->getVersion(),
                "main" => $description->getMain(),
                "api" => $description->getCompatibleApis(),
                "depend" => $description->getDepend(),
                "description" => $description->getDescription(),
                "authors" => $description->getAuthors(),
                "website" => $description->getWebsite(),
                "creationDate" => time()
            ]);
        }
        if(!$setting["skip-stub"]){
            $phar->setStub('<?php echo "PocketMine-MP plugin ' . "{$description->getName()}_v{$description->getVersion()}\nThis file has been generated using MakePluginPlus at " . date("r") . '\n----------------\n";if(extension_loaded("phar")){$phar = new \Phar(__FILE__);foreach($phar->getMetadata() as $key => $value){echo ucfirst($key).": ".(is_array($value) ? implode(", ", $value):$value)."\n";}} __HALT_COMPILER();');
        }else{
            $phar->setStub("<?php __HALT_COMPILER();");
        }

        if(file_exists($buildFolder = "{$this->getDataFolder()}build/")){
            Utils::removeDirectory($buildFolder);
        }
        mkdir($buildFolder);
        foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($filePath)) as $path => $fileInfo){
            $fileName = $fileInfo->getFilename();
            if($fileName !== "." && $fileName !== ".."){
                $inPath = substr($path, strlen($filePath));
                if(!$setting["include-minimal"] || $inPath === "plugin.yml" || strpos($inPath, "src\\") === 0 || strpos($inPath, "resources\\") === 0){
                    $newFilePath = "{$buildFolder}{$inPath}";
                    $newFileDir = dirname($newFilePath);
                    if(!file_exists($newFileDir)){
                        mkdir($newFileDir, 0777, true);
                    }
                    if(substr($path, -4) == ".php"){
                        $contents = \file_get_contents($path);
                        if($setting["code-optimize"]){
                            $contents = Utils::codeOptimize($contents);
                        }
                        if($setting["rename-variable"]){
                            $contents = Utils::renameVariable($contents);
                        }
                        if($setting["remove-comment"]){
                            $contents = Utils::removeComment($contents);
                        }
                        if($setting["remove-whitespace"]){
                            $contents = Utils::removeWhitespace($contents);
                        }
                        file_put_contents($newFilePath, $contents);
                    }else{
                        copy($path, $newFilePath);
                    }
                }
            }
        }
        $phar->startBuffering();
        $phar->buildFromDirectory($buildFolder);
        if($setting["compress"] && \Phar::canCompress(\Phar::GZ)){
            $phar->compressFiles(\Phar::GZ);
        }
        $phar->stopBuffering();
        Utils::removeDirectory("{$this->getDataFolder()}build/");
    }
}
