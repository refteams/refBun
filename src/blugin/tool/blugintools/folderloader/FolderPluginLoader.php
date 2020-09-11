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

namespace blugin\tool\blugintools\folderloader;

use pocketmine\plugin\PluginDescription;
use pocketmine\plugin\PluginLoader;
use pocketmine\Server;

class FolderPluginLoader implements PluginLoader{
    /** @var \ClassLoader */
    private $loader;

    public function __construct(\ClassLoader $loader){
        $this->loader = $loader;
    }

    public function canLoadPlugin(string $path) : bool{
        return is_file($path . "/plugin.yml") && is_dir($path . "/src/");
    }

    public function loadPlugin(string $file) : void{
        $this->loader->addPath("$file/src");
    }

    public function getPluginDescription(string $file) : ?PluginDescription{
        if(is_file($ymlFile = $file . "/plugin.yml")){
            if(!empty($yml = file_get_contents($ymlFile))){
                $description = new PluginDescription($yml);
                //Prevent load exists plugin
                return Server::getInstance()->getPluginManager()->getPlugin($description->getName()) === null ? $description : null;
            }
        }

        return null;
    }

    public function getAccessProtocol() : string{
        return "";
    }
}
