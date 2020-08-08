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
use PhpParser\Node\Const_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Stmt\ClassConst;

class PrivateConstRenamingVisitor extends RenamerHolderVisitor{
    /** @var Const_[] */
    private $privateConsts = [];

    /**
     * Register private consts on before traverse
     *
     * @param Node[] $nodes
     *
     * @return array
     **/
    public function beforeTraverse(array $nodes){
        $this->getRenamer()->init();
        $this->privateConsts = [];
        $this->registerPrivateConsts($nodes);
        return $nodes;
    }

    /**
     * Register private consts with recursion
     *
     * @param Node[] $nodes
     *
     * @return void
     **/
    private function registerPrivateConsts(array $nodes) : void{
        foreach($nodes as $node){
            if($node instanceof ClassConst && $node->isPrivate()){
                foreach($node->consts as $const){
                    $this->privateConsts[] = $const;
                    $this->generate($const);
                }
            }

            //Child node with recursion processing
            if(isset($node->stmts) && is_array($node->stmts)){
                $this->registerPrivateConsts($node->stmts);
            }
        }
    }

    /**
     * @param Node $node
     *
     * @return Node
     */
    protected function getTarget(Node $node) : Node{
        if($node instanceof ClassConstFetch || $node instanceof Const_){
            return $node->name;
        }
        return $node;
    }

    /**
     * @param Node   $node
     * @param string $property
     *
     * @return bool
     */
    protected function isValidToGenerate(Node $node, string $property = "name") : bool{
        return parent::isValidToRename($node, $property) && $node instanceof Const_ && in_array($node, $this->privateConsts);;
    }

    /**
     * @param Node   $node
     * @param string $property
     *
     * @return bool
     */
    protected function isValidToRename(Node $node, string $property = "name") : bool{
        return parent::isValidToRename($node, $property) && ($node instanceof ClassConstFetch || $node instanceof Const_);
    }
}