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
 * @author  Blugin team
 * @link    https://github.com/Blugin
 * @license https://www.gnu.org/licenses/mit MIT License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 *
 * @name BluginToolsLoader
 * @api 4.0.0
 * @version 1.0.0
 * @main blugin\tool\blugintools\blugintoolsloader\BluginToolsLoader
 * @load STARTUP
 */

namespace blugin\tool\blugintools\blugintoolsloader;

use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginDescription;
use pocketmine\plugin\PluginLoader;
use pocketmine\plugin\PluginLoadOrder;

class BluginToolsLoader extends PluginBase{
    public function onEnable(){
        $this->getServer()->getPluginManager()->registerInterface($loader = new class($this->getServer()->getLoader()) implements PluginLoader{
            /** @var \ClassLoader */
            private $loader;

            public function __construct(\ClassLoader $loader){
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
                        //Load BluginTools only
                        return $description->getName() === "BluginTools" ? $description : null;
                    }
                }

                return null;
            }

            public function getAccessProtocol() : string{
                return "";
            }
        });
        $this->getServer()->getPluginManager()->loadPlugins($this->getServer()->getPluginPath(), [get_class($loader)]);
        $this->getServer()->enablePlugins(PluginLoadOrder::STARTUP());
    }
}