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
use blugin\tool\dev\builder\BluginBuilder;
use pocketmine\plugin\PluginBase;

class BluginTools extends PluginBase{
    use MultilingualConfigTrait;

    /** @var BluginTools */
    private static $instance;

    /** @return BluginTools */
    public static function getInstance() : BluginTools{
        return self::$instance;
    }

    /** @var BluginBuilder */
    private $builder = null;

    public function onLoad(){
        self::$instance = $this;

        $this->builder = new BluginBuilder($this);
    }

    public function onEnable(){
        $this->saveResource("virion/virion.php");
        $this->saveResource("virion/virion_stub.php");

        $this->builder->init();
    }

    public function getBuilder() : BluginBuilder{
        return $this->builder;
    }
}
