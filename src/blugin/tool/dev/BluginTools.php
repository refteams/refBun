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

namespace blugin\tool\dev;

use blugin\lib\translator\traits\MultilingualConfigTrait;
use blugin\tool\dev\builder\AdvancedBuilder;
use blugin\tool\dev\folderloader\FolderPluginLoader;
use blugin\tool\dev\virion\VirionLoader;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginLoadOrder;

class BluginTools extends PluginBase{
    use MultilingualConfigTrait;

    /** @var BluginTools */
    private static $instance;

    /** @return BluginTools */
    public static function getInstance() : BluginTools{
        return self::$instance;
    }

    public function onLoad(){
        self::$instance = $this;

        AdvancedBuilder::getInstance();
        VirionLoader::getInstance();
        $this->getServer()->getPluginManager()->registerInterface(new FolderPluginLoader($this->getServer()->getLoader()));
    }

    public function onEnable(){
        $this->getServer()->getPluginManager()->loadPlugins($this->getServer()->getPluginPath(), [FolderPluginLoader::class]);
        $this->getServer()->enablePlugins(PluginLoadOrder::STARTUP);
        AdvancedBuilder::getInstance()->init();
    }
}
