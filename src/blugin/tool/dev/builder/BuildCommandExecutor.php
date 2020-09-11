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

namespace blugin\tool\dev\builder;

use blugin\tool\dev\BluginTools;
use blugin\tool\dev\folderloader\FolderPluginLoader as BluginPluginLoader;
use FolderPluginLoader\FolderPluginLoader as DevToolsPluginLoader;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class BuildCommandExecutor implements CommandExecutor{
    /** @var AdvancedBuilder */
    private $builder;

    public function __construct(AdvancedBuilder $plugin){
        $this->builder = $plugin;
    }

    /**
     * @param string[] $args
     *
     * @throws \ReflectionException
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if(empty($args))
            return false;

        /** @var PluginBase[] $plugins */
        $plugins = [];
        $pluginManager = Server::getInstance()->getPluginManager();
        if($args[0] === "*"){
            foreach($pluginManager->getPlugins() as $pluginName => $plugin){
                if($plugin->getPluginLoader() instanceof DevToolsPluginLoader || $plugin->getPluginLoader() instanceof BluginPluginLoader){
                    $plugins[$plugin->getName()] = $plugin;
                }
            }
        }else{
            foreach($args as $key => $pluginName){
                $plugin = BluginTools::getPlugin($pluginName);
                if($plugin === null){
                    $sender->sendMessage("{$pluginName} is invalid plugin name");
                }else{
                    $plugins[$plugin->getName()] = $plugin;
                }
            }
        }
        $pluginCount = count($plugins);
        $sender->sendMessage("Start build the {$pluginCount} plugins");

        if(!file_exists($dataFolder = BluginTools::getInstance()->getDataFolder())){
            mkdir($dataFolder, 0777, true);
        }
        foreach($plugins as $pluginName => $plugin){
            $pharName = "{$pluginName}_v{$plugin->getDescription()->getVersion()}.phar";
            $this->buildPlugin($plugin);
            $sender->sendMessage("$pharName has been created on $dataFolder");
        }
        $sender->sendMessage("Complete built the {$pluginCount} plugins");
        return true;
    }

    /** @throws \ReflectionException */
    public function buildPlugin(PluginBase $plugin) : void{
        $reflection = new \ReflectionClass(PluginBase::class);
        $fileProperty = $reflection->getProperty("file");
        $fileProperty->setAccessible(true);
        $sourcePath = rtrim(realpath($fileProperty->getValue($plugin)), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $pharPath = BluginTools::getInstance()->getDataFolder() . "{$plugin->getName()}_v{$plugin->getDescription()->getVersion()}.phar";

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
        $this->builder->buildPhar($sourcePath, $pharPath, $metadata);
    }
}
