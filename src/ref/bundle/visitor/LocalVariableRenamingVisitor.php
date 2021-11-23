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
use PhpParser\Node\Expr\ClosureUse;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\StaticVar;
use PhpParser\NodeVisitorAbstract;
use ref\bundle\renamer\IRenamerHolder;
use ref\bundle\traits\renamer\RenamerHolderVisitorTrait;

use function in_array;
use function is_string;

class LocalVariableRenamingVisitor extends NodeVisitorAbstract implements IRenamerHolder{
    use RenamerHolderVisitorTrait;

    /** @const string[] list of ignore name, The global variables and $this */
    private const IGNORE_LIST = [
        "this",
        "_GET",
        "_POST",
        "_SERVER",
        "_REQUEST",
        "_COOKIE",
        "_SESSION",
        "_ENV",
        "_FILES"
    ];

    protected function getTarget(Node $node) : Node{
        if($node instanceof Param || $node instanceof StaticVar || $node instanceof Catch_ || $node instanceof ClosureUse){
            return $node->var;
        }
        return $node;
    }

    protected function isValid(Node $node, string $property = "name") : bool{
        $target = $this->getTarget($node);
        //Ignore to rename if it not strings or global variable or $this(ex: $$varName, $_GET, $this)
        return isset($target->$property) && is_string($target->$property) && $target instanceof Variable && is_string($target->name) && !in_array($target->name, self::IGNORE_LIST, true);
    }
}