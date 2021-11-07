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

namespace ref\tool\reftools\visitor;

use ref\tool\reftools\renamer\IRenamerHolder;
use ref\tool\reftools\traits\renamer\RenamerHolderVisitorTrait;
use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeVisitorAbstract;

use function in_array;
use function is_array;

abstract class PrivateRenamingVisitor extends NodeVisitorAbstract implements IRenamerHolder{
    use RenamerHolderVisitorTrait;

    /** @var Const_[] */
    protected array $privateNodes = [];

    /**
     * @param Node[] $nodes
     *
     * @return Node[]|null
     **/
    public function beforeTraverse(array $nodes) : ?array{
        $this->getRenamer()->init();
        $this->privateNodes = [];
        $this->registerPrivateNodes($nodes);
        return $nodes;
    }

    /** @param Node[] $nodes * */
    private function registerPrivateNodes(array $nodes) : void{
        foreach($nodes as $node){
            if($node instanceof ClassLike && !$node instanceof Class_)
                continue;

            $this->registerNode($node);

            //Child node with recursion processing
            if(isset($node->stmts) && is_array($node->stmts)){
                $this->registerPrivateNodes($node->stmts);
            }
        }
    }

    /** Register private node **/
    abstract protected function registerNode(Node $node) : void;

    /** Filter is target node */
    abstract protected function isTarget(Node $node) : bool;

    protected function getTarget(Node $node) : Node{
        if($this->isTarget($node)){
            return $node->name;
        }
        return $node;
    }

    protected function isValidToGenerate(Node $node, string $property = "name") : bool{
        return $this->isValid($node, $property) && in_array($node, $this->privateNodes);
    }

    protected function isValidToRename(Node $node, string $property = "name") : bool{
        return $this->isValid($node, $property) && $this->isTarget($node);
    }
}