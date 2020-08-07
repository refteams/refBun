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

namespace blugin\tool\builder\visitor\renamer;

use PhpParser\Node;

abstract class Renamer{
    /** @var string[] original name => new name */
    protected $nameTable = [];

    public function init() : void{
        $this->nameTable = [];
    }

    /**
     * @param Node   $node
     * @param string $property = "name"
     */
    public abstract function generate(Node $node, string $property = "name") : void;

    /**
     * @param Node   $node
     * @param string $property = "name"
     *
     * @return Node|null
     */
    public function rename(Node $node, string $property = "name") : ?Node{
        $newName = $this->nameTable[$node->$property] ?? null;
        if(!$newName)
            return null;

        $node->$property = $newName;
        return $node;
    }
}