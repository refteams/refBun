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

namespace blugin\tool\builder\visitor;

use PhpParser\Node;
use PhpParser\Node\Expr\ClosureUse;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\StaticVar;

class VariableRenamerVisitor extends RenamerHolderVisitor{
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

    /**
     * Generate name of variable on enter
     *
     * @param Node $node
     *
     * @return Node|null
     */
    public function enterNode(Node $node){
        $this->generate($this->getChildVariable($node));
        return null;
    }

    /**
     * Rename variable on leave
     *
     * @param Node $node
     *
     * @return Node|null
     */
    public function leaveNode(Node $node){
        $this->rename($this->getChildVariable($node));
        return null;
    }

    /**
     * @param Node $node
     *
     * @return Node
     */
    public function getChildVariable(Node $node) : Node{
        if($node instanceof Param || $node instanceof StaticVar || $node instanceof Catch_ || $node instanceof ClosureUse){
            return $node->var;
        }
        return $node;
    }

    /**
     * @param Node   $node
     * @param string $property
     *
     * @return bool
     */
    public function isValid(Node $node, string $property = "name") : bool{
        //Ignore to rename if it not string or global variable or $this(ex: $$varname, $_GET, $this)
        return parent::isValid($node, $property) && $node instanceof Variable && is_string($node->name) && !in_array($node->name, self::IGNORE_LIST);
    }
}