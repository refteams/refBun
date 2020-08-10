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

use blugin\tool\builder\visitor\renamer\IRenamerHolder;
use blugin\tool\builder\visitor\renamer\RenamerHolderVisitorTrait;
use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\NodeVisitorAbstract;

abstract class PrivateRenamingVisitor extends NodeVisitorAbstract implements IRenamerHolder{
    use RenamerHolderVisitorTrait;

    /** @var Const_[] */
    protected $privateNodes = [];

    /**
     * Register private nodes on before traverse
     *
     * @param Node[] $nodes
     *
     * @return array
     **/
    public function beforeTraverse(array $nodes){
        $this->getRenamer()->init();
        $this->privateNodes = [];
        $this->registerPrivateNodes($nodes);
        return $nodes;
    }

    /**
     * Register private nodes with recursion
     *
     * @param Node[] $nodes
     *
     * @return void
     **/
    private function registerPrivateNodes(array $nodes) : void{
        foreach($nodes as $node){
            $this->registerNode($node);

            //Child node with recursion processing
            if(isset($node->stmts) && is_array($node->stmts)){
                $this->registerPrivateNodes($node->stmts);
            }
        }
    }

    /**
     * Register private node
     *
     * @param Node $node
     **/
    abstract protected function registerNode(Node $node) : void;

    /**
     * Filter is target node
     *
     * @param Node $node
     *
     * @return bool
     */
    abstract protected function isTarget(Node $node) : bool;

    /**
     * @param Node $node
     *
     * @return Node
     */
    protected function getTarget(Node $node) : Node{
        if($this->isTarget($node)){
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
        return $this->isValid($node, $property) && in_array($node, $this->privateNodes);
    }

    /**
     * @param Node   $node
     * @param string $property
     *
     * @return bool
     */
    protected function isValidToRename(Node $node, string $property = "name") : bool{
        return $this->isValid($node, $property) && $this->isTarget($node);
    }
}