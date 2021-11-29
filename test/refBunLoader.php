<?php

/**
 *  ____  _             _         _____
 * | __ )| |_   _  __ _(_)_ __   |_   _|__  __ _ _ __ ___
 * |  _ \| | | | |/ _` | | '_ \    | |/ _ \/ _` | '_ ` _ \
 * | |_) | | |_| | (_| | | | | |   | |  __/ (_| | | | | | |
 * |____/|_|\__,_|\__, |_|_| |_|   |_|\___|\__,_|_| |_| |_|
 *                |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the MIT License.
 *
 * @author  ref team
 * @link    https://github.com/refteams
 * @license https://www.gnu.org/licenses/mit MIT License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 *
 * @name refBunLoader
 * @api 4.0.0
 * @version 1.0.0
 * @main ref\bundle\refBunLoader
 * @load STARTUP
 *
 * @noinspection PhpUndefinedFieldInspection
 * @noinspection PhpUndefinedMethodInspection
 */

namespace ref\bundle;

use ClassLoader;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginDescription;
use pocketmine\plugin\PluginEnableOrder;
use pocketmine\plugin\PluginLoader;
use pocketmine\scheduler\ClosureTask;

use function file_get_contents;
use function get_class;
use function is_dir;
use function is_file;

final class refBunLoader extends PluginBase{
    public string $pluginLoaderClass;

    protected function onEnable() : void{
        $server = $this->getServer();
        $pluginManager = $server->getPluginManager();

        $pluginManager->registerInterface($pluginLoader = new class($server->getLoader()) implements PluginLoader{
            private ClassLoader $loader;

            public function __construct(ClassLoader $loader){
                $this->loader = $loader;
            }

            public function canLoadPlugin(string $path) : bool{
                return is_file($path . "/plugin.yml") && is_dir($path . "/src/");
            }

            /** @load STARTUP */
            public function loadPlugin(string $file) : void{
                $this->loader->addPath("", "$file/src");
            }

            public function getPluginDescription(string $file) : ?PluginDescription{
                if(is_file($ymlFile = $file . "/plugin.yml")){
                    if(!empty($yml = file_get_contents($ymlFile))){
                        $description = new PluginDescription($yml);
                        return $description->getName() === "refBun" ? $description : null;
                    }
                }

                return null;
            }

            public function getAccessProtocol() : string{
                return "";
            }
        });
        $this->pluginLoaderClass = get_class($pluginLoader);

        $pluginManager->loadPlugins($server->getPluginPath());
        $server->enablePlugins(PluginEnableOrder::STARTUP());

        $this->getScheduler()->scheduleTask(new ClosureTask(fn() => $pluginManager->disablePlugin($this)));
    }

    protected function onDisable() : void{
        (function(refBunLoader $plugin){ //HACK : Closure bind hack to access inaccessible members
            /** @see \pocketmine\plugin\PluginManager::$plugins */
            unset($this->plugins[$plugin->getDescription()->getName()]);

            /** @see \pocketmine\plugin\PluginManager::$fileAssociations */
            unset($this->fileAssociations[$plugin->pluginLoaderClass]);
        })->call($this->getServer()->getPluginManager(), $this);
    }
}