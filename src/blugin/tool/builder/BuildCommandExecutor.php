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

use FolderPluginLoader\FolderPluginLoader;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class BuildCommandExecutor implements CommandExecutor{
    /** @var BluginBuilder */
    private $plugin;

    /** @param BluginBuilder $plugin */
    public function __construct(BluginBuilder $plugin){
        $this->plugin = $plugin;
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
        if(empty($args))
            return false;

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
                $plugin = $this->getPlugin($pluginName);
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

        if(!file_exists($dataFolder = $this->plugin->getDataFolder())){
            mkdir($dataFolder, 0777, true);
        }
        foreach($plugins as $pluginName => $plugin){
            $pharName = "{$pluginName}_v{$plugin->getDescription()->getVersion()}.phar";
            $this->plugin->buildPlugin($plugin);
            $sender->sendMessage("$pharName has been created on $dataFolder");
        }
        $sender->sendMessage("Complete built the {$pluginCount} plugins");
        return true;
    }

    /**
     * @param string $name
     *
     * @return null|Plugin
     */
    private function getPlugin(string $name) : ?Plugin{
        $plugins = Server::getInstance()->getPluginManager()->getPlugins();
        if(isset($plugins[$name]))
            return $plugins[$name];

        $found = null;
        $length = strlen($name);
        $minDiff = PHP_INT_MAX;
        foreach($plugins as $pluginName => $plugin){
            if(stripos($pluginName, $name) === 0){
                $diff = strlen($pluginName) - $length;
                if($diff < $minDiff){
                    $found = $plugin;
                    if($diff === 0)
                        break;

                    $minDiff = $diff;
                }
            }
        }
        return $found;
    }
}
