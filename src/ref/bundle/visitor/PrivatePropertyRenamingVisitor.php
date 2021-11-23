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

namespace ref\bundle\visitor;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;

class PrivatePropertyRenamingVisitor extends PrivateRenamingVisitor{
    protected function registerNode(Node $node) : void{
        if($node instanceof Property && $node->isPrivate()){
            foreach($node->props as $prop){
                $this->privateNodes[] = $prop;
                $this->generate($prop);
            }
        }
    }

    protected function isTarget(Node $node) : bool{
        return
            $node instanceof PropertyProperty ||
            ($node instanceof PropertyFetch && $node->var instanceof Variable && $node->var->name === "this") ||
            ($node instanceof StaticPropertyFetch && $node->class instanceof Name && $node->class->parts[0] === "self");
    }
}