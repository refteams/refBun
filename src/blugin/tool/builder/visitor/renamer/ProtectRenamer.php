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
 *  ( . .) ♥️
 *  c(")(")
 */

declare(strict_types=1);

namespace blugin\tool\builder\visitor\renamer;

use PhpParser\Node;

class ProtectRenamer extends Renamer{
    private const BARRIOR = "\u{20E0}";

    /**
     * @param Node   $node
     * @param string $property = "name"
     */
    public function generate(Node $node, string $property = "name") : void{
        if($node !== null && isset($node->$property) && is_string($node->$property) && !isset($this->nameTable[$node->$property]))
            $this->nameTable[$node->$property] = str_repeat(self::BARRIOR, count($this->nameTable) + 1);
    }
}