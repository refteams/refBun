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

namespace blugin\tool\dev\builder\renamer;

use PhpParser\Node;

class SpaceRenamer extends Renamer{
    private const SPACE_TABLE = [
        "\u{2000}",
        "\u{2001}",
        "\u{2002}",
        "\u{2003}",
        "\u{2004}",
        "\u{2005}",
        "\u{2006}",
        "\u{2007}",
        "\u{2008}",
        "\u{2009}",
        "\u{200A}",
        "\u{2028}",
        "\u{205F}",
        "\u{3000}"
    ];

    /**
     * @param Node   $node
     * @param string $property = "name"
     */
    public function generate(Node $node, string $property = "name") : void{
        if($this->getName($node->$property) !== null)
            return;

        $variableCount = count($this->getNameTable());
        $newName = self::SPACE_TABLE[$variableCount % count(self::SPACE_TABLE)];
        if($variableCount){
            if(($sub = floor($variableCount / count(self::SPACE_TABLE)) - 1) > -1){
                $newName .= self::SPACE_TABLE[$sub];
            }
        }
        $this->setName($node->$property, $newName);
    }
}