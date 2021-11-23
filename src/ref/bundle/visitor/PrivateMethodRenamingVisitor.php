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
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;

class PrivateMethodRenamingVisitor extends PrivateRenamingVisitor{
    protected function registerNode(Node $node) : void{
        if($node instanceof ClassMethod && $node->isPrivate()){
            $this->privateNodes[] = $node;
            $this->generate($node);
        }
    }

    protected function isValid(Node $node, string $property = "name") : bool{
        return parent::isValid($node, $property) && !str_starts_with($this->getTarget($node)->$property, "__");
    }

    protected function isTarget(Node $node) : bool{
        return
            $node instanceof ClassMethod ||
            ($node instanceof MethodCall && $node->var instanceof Variable && $node->var->name === "this") ||
            ($node instanceof StaticCall && $node->class instanceof Name && $node->class->parts[0] === "self");
    }
}