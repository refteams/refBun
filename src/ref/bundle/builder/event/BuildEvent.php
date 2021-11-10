<?php

/**
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

namespace ref\bundle\builder\event;

use ref\bundle\refBun;
use ref\bundle\builder\Builder;
use pocketmine\event\Event;
use pocketmine\utils\Config;

abstract class BuildEvent extends Event{
    private Builder $builder;

    private string $sourceDir;

    private string $prepareDir;

    private string $resultDir;

    private Config $option;

    private string $resultPath;

    public function __construct(Builder $builder, string $sourceDir, string $resultPath, Config $option){
        $this->builder = $builder;
        $this->sourceDir = $sourceDir;
        $this->resultPath = $resultPath;
        $this->option = $option;

        $this->prepareDir = refBun::loadDir(Builder::DIR_PREPARE);
        $this->resultDir = refBun::loadDir(Builder::DIR_RESULT);
    }

    public function getBuilder() : Builder{
        return $this->builder;
    }

    public function getSourceDir() : string{
        return $this->sourceDir;
    }

    public function getResultDir() : string{
        return $this->resultDir;
    }

    public function getPrepareDir() : string{
        return $this->prepareDir;
    }

    public function getResultPath() : string{
        return $this->resultPath;
    }

    public function getOption() : Config{
        return $this->option;
    }

    public function isArchive() : bool{
        return substr($this->resultPath, -strlen(".phar")) === ".phar";
    }

    public function isScript() : bool{
        return substr($this->resultPath, -strlen(".php")) === ".php";
    }
}
