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

use blugin\tool\builder\visitor\renamer\Renamer;
use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassConst;

class PrivateConstRenamingVisitor extends PrivateRenamingVisitor{
    /** @param Renamer $renamer */
    public function setRenamer(Renamer $renamer) : void{
        $this->renamer = $renamer;
        $renamer->setIgnorecase();
    }

    /**
     * Register private node
     *
     * @param Node $node
     **/
    protected function registerNode(Node $node) : void{
        if($node instanceof ClassConst && $node->isPrivate()){
            foreach($node->consts as $const){
                $this->privateNodes[] = $const;
                $this->generate($const);
            }
        }
    }

    /**
     * Filter is target node
     *
     * @param Node $node
     *
     * @return bool
     */
    protected function isTarget(Node $node) : bool{
        return $node instanceof Const_
            || $node instanceof ClassConstFetch && $node->class instanceof Name && $node->class->parts[0] === "self";
    }
}