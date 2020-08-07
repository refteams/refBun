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
        $variable = $this->getVariableFromNode($node);
        if($variable === null || !$this->isValidVariable($variable))
            return null;

        $this->renamer->generate($variable);
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
        $variable = $this->getVariableFromNode($node);
        if($variable === null || !$this->isValidVariable($variable))
            return null;

        $this->renamer->rename($variable);
        return null;
    }

    /**
     * @param Node $node
     *
     * @return Variable|null
     */
    public function getVariableFromNode(Node $node) : ?Variable{
        if($node instanceof Variable){
            return $node;
        }elseif($node instanceof Param || $node instanceof StaticVar || $node instanceof Catch_ || $node instanceof ClosureUse){
            return $node->var;
        }
        return null;
    }

    /**
     * @param Variable $variable
     *
     * @return bool
     */
    public function isValidVariable(Variable $variable) : bool{
        //Ignore to rename if it not string or global variable or $this(ex: $$varname, $_GET, $this)
        return is_string($variable->name) && !in_array($variable->name, self::IGNORE_LIST);
    }
}