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

namespace ref\bundle\visitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeVisitorAbstract;

use function is_array;
use function usort;

class ImportSortingVisitor extends NodeVisitorAbstract{
    /**
     * @param Node[] $nodes
     **/
    public function afterTraverse(array $nodes) : ?array{
        $this->readUses($nodes);
        return $nodes;
    }

    /** @param Node[] $nodes */
    private function readUses(array $nodes) : void{
        foreach($nodes as $node){
            if($node instanceof Namespace_){
                usort($node->stmts, function(Node $a, Node $b){
                    /** @var Use_|GroupUse $a */
                    /** @var Use_|GroupUse $b */
                    if($this->isUse($a) && $this->isUse($b)){
                        $typeDiff = $a->type <=> $b->type;
                        if($typeDiff !== 0)
                            return $typeDiff;
                        return $this->getName($a) <=> $this->getName($b);
                    }
                    return (int) !$this->isUse($a);
                });
            }elseif($this->isUse($node)){
                usort($node->uses, function(UseUse $a, UseUse $b){
                    return $a->name->toCodeString() <=> $b->name->toCodeString();
                });
            }

            //Child node with recursion processing
            if(isset($node->stmts) && is_array($node->stmts)){
                $this->readUses($node->stmts);
            }
        }
    }

    private function isUse(Node $node) : bool{
        return $node instanceof Use_ || $node instanceof GroupUse;
    }

    private function getName(Node $node) : string{
        if($node instanceof Use_){
            return $node->uses[0]->name->toCodeString();
        }elseif($node instanceof GroupUse){
            return $node->prefix->toCodeString();
        }else{
            return $node->getType();
        }
    }
}