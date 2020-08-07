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

class SerialRenamer extends Renamer{
    /** @var string[] */
    private $firstChars, $otherChars;
    /** @var int */
    private $firstCount, $otherCount;

    public function __construct(){
        $this->firstChars = $firstChars = array_merge(["_"], range("a", "z"), range("A", "Z"));
        $this->otherChars = array_merge(range("0", "9"), $this->firstChars);
        $this->firstCount = count($this->firstChars);
        $this->otherCount = count($this->otherChars);
    }

    /**
     * @param Node   $node
     * @param string $property = "name"
     */
    public function generate(Node $node, string $property = "name") : void{
        if($node === null || isset($this->nameTable[$node->$property]))
            return;

        $variableCount = count($this->nameTable);
        $variableName = $this->firstChars[$variableCount % $this->firstCount];
        if($variableCount){
            if(($sub = floor($variableCount / $this->firstCount) - 1) > -1){
                $variableName .= $this->otherChars[$sub];
            }
        }
        $this->nameTable[$node->$property] = $variableName;
    }
}