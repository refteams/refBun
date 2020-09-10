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

class BuildPrepareEvent extends BuildEvent{
    /** @var string[] path => new path */
    protected $files = [];

    /** @return string[] */
    public function getFiles() : array{
        return $this->files;
    }

    /** @param string[] $files */
    public function setFiles(array $files) : void{
        $this->files = $files;
    }

    public function addFile(string $path, string $newPath) : void{
        $this->files[$path] = $newPath;
    }

    public function removeFile(string $path) : void{
        unset($this->files[$path]);
    }
}
