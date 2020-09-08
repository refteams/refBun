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

namespace blugin\tool\dev\builder\event;

use blugin\tool\dev\builder\AdvancedBuilder;
use pocketmine\event\Event;
use pocketmine\utils\Config;

abstract class BuildEvent extends Event{
    /** @var AdvancedBuilder */
    private $builder;

    /** @var string */
    private $projectDir;

    /** @var Config */
    private $option;

    public function __construct(AdvancedBuilder $builder, string $projectDir, Config $option){
        $this->builder = $builder;
        $this->projectDir = $projectDir;
        $this->option = $option;
    }

    public function getBuilder() : AdvancedBuilder{
        return $this->builder;
    }

    public function getProjectDir() : string{
        return $this->projectDir;
    }

    public function getOption() : Config{
        return $this->option;
    }
}
