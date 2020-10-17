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

namespace blugin\tool\blugintools\builder\event;

use blugin\tool\blugintools\BluginTools;
use blugin\tool\blugintools\builder\Builder;
use pocketmine\event\Event;
use pocketmine\utils\Config;

abstract class BuildEvent extends Event{
    /** @var Builder */
    private $builder;

    /** @var string */
    private $sourceDir;

    /** @var string */
    private $prepareDir;

    /** @var string */
    private $buildedDir;

    /** @var string */
    private $resultPath;

    /** @var Config */
    private $option;

    public function __construct(Builder $builder, string $sourceDir, string $resultPath, Config $option){
        $this->builder = $builder;
        $this->sourceDir = $sourceDir;
        $this->resultPath = $resultPath;
        $this->option = $option;

        $this->prepareDir = BluginTools::loadDir(Builder::DIR_PREPARE);
        $this->buildedDir = BluginTools::loadDir(Builder::DIR_BUILDED);
    }

    public function getBuilder() : Builder{
        return $this->builder;
    }

    public function getSourceDir() : string{
        return $this->sourceDir;
    }

    public function getBuildedDir() : string{
        return $this->buildedDir;
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
}
