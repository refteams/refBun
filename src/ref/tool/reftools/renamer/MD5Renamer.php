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

namespace ref\tool\reftools\renamer;

use PhpParser\Node;

use function lcg_value;
use function md5;
use function mt_rand;
use function range;
use function substr;

class MD5Renamer extends Renamer{
    public function generate(Node $node, string $property = "name") : void{
        $nameTable = $this->getNameTable();
        if(isset($nameTable[$node->$property]))
            return;

        do{
            $hash = md5($node->$property . lcg_value());
            if($this->requireInitialValid()){
                $hash = range("a", "z")[mt_rand(0, 25)] . $hash;
            }
            $newName = substr($hash, 0, 5);
        }while($this->in_array($newName, $nameTable));

        $this->setName($node->$property, $newName);
    }
}