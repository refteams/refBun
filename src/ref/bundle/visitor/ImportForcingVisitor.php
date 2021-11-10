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
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeVisitor\NameResolver;

use function array_unshift;
use function defined;
use function function_exists;
use function is_array;
use function ltrim;

class ImportForcingVisitor extends NameResolver{
    use GetFullyQualifiedTrait;

    /** @var UseUse[] */
    private array $uses = [], $newUses = [];

    /**
     * @param Node[] $nodes
     *
     * @return Node[]|null
     **/
    public function beforeTraverse(array $nodes) : ?array{
        $this->nameContext->startNamespace();
        $this->uses = [];
        $this->newUses = [];
        $this->registerUses($nodes);
        return $nodes;
    }

    /**
     * @param Node[] $nodes
     *
     * @return Node[]|null
     **/
    public function afterTraverse(array $nodes) : ?array{
        $this->appendUsesToNamespace($nodes);
        return $nodes;
    }

    protected function resolveName(Name $name, int $type) : Name{
        $result = parent::resolveName($name, $type);
        $code = $result->toCodeString();
        if($result instanceof FullyQualified){
            if(!isset($this->uses[$code])){
                if($result->isUnqualified()){ //Replace to global function/constant
                    return $this->resolveGlobal($result) ?? $name;
                }
                $this->newUses[$code] = new UseUse(new Name(ltrim($code, "\\")), $result->getLast() === $name->getLast() ? null : new Node\Identifier($name->toCodeString()), $type);
                return new Name($name->getLast(), $name->getAttributes());
            }
        }else{
            return $this->resolveGlobal($result) ?? $name;
        }
        return $name; //Return original name instead of resolved name
    }

    /** @param Node[] $nodes */
    private function registerUses(array $nodes) : void{
        foreach($nodes as $node){
            if($node instanceof Use_ || $node instanceof GroupUse){
                foreach($node->uses as $use){
                    $this->uses[$this->getFullyQualifiedString($use, $node, false)] = $use;
                }
            }

            //Child node with recursion processing
            if(isset($node->stmts) && is_array($node->stmts)){
                $this->registerUses($node->stmts);
            }
        }
    }

    /** @param Node[] $nodes */
    private function appendUsesToNamespace(array $nodes) : void{
        foreach($nodes as $node){
            if($node instanceof Namespace_){
                foreach($node->stmts as $child){
                    if($child instanceof ClassLike){
                        unset($this->newUses["\\" . $child->namespacedName->toCodeString()]);
                    }
                }
                foreach($this->newUses as $use){
                    array_unshift($node->stmts, new Use_([$use]));
                }
                return;
            }

            //Child node with recursion processing
            if(isset($node->stmts) && is_array($node->stmts)){
                $this->appendUsesToNamespace($node->stmts);
            }
        }
    }

    private function resolveGlobal(Name $name) : Name{
        $code = ltrim($name->toCodeString(), "\\");
        if($code === "self" || $code === "parent" || $code === "static")
            return $name;

        $fullCode = "\\" . $code;
        $use = new UseUse(new Name($code), null, Use_::TYPE_UNKNOWN);
        if(function_exists($fullCode)){
            $use->type = Use_::TYPE_FUNCTION;
        }elseif(defined($fullCode)){
            $use->type = Use_::TYPE_CONSTANT;
        }
        $this->newUses[$fullCode] = $use;
        return new Name($code, $name->getAttributes());
    }
}