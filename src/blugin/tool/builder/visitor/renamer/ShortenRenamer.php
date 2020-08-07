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

class ShortenRenamer extends Renamer{
    /** @param Node $node */
    public function generateName(Node $node) : void{
        $variable = $this->getVariableFromNode($node);
        if($variable === null || !$this->isValidVariable($variable) || isset($this->nameTable[$variable->name]))
            return;

        $name = $variable->name;
        $length = 1;
        $num = 0;
        do{
            $newName = substr($name, 0, $length++);
            if($newName === $variable->name || $length > 2)
                $newName = substr($name, 0, 2) . $num++;
        }while(in_array($newName, $this->nameTable));

        $this->nameTable[$variable->name] = $newName;
    }
}