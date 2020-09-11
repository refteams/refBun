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

namespace blugin\tool\blugintools\builder\visitor;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeVisitor\NameResolver;

class ImportForcingVisitor extends NameResolver{
    use GetFullyQualifiedTrait;

    /** @var UseUse[] */
    private $uses = [], $newUses = [];

    /**
     * @param Node[] $nodes
     *
     * @return array
     **/
    public function beforeTraverse(array $nodes){
        $this->nameContext->startNamespace();
        $this->uses = [];
        $this->newUses = [];
        $this->registerUses($nodes);
        return $nodes;
    }

    /**
     * @param Node[] $nodes
     *
     * @return array
     **/
    public function afterTraverse(array $nodes){
        $this->appendUsesToNamespace($nodes);
        return $nodes;
    }

    /**
     * @param Name $name
     * @param int  $type
     *
     * @return Name
     */
    protected function resolveName(Name $name, int $type) : Name{
        $originalName = str_replace("\\", "", $name->toCodeString());
        $result = parent::resolveName($name, $type);
        $code = $result->toCodeString();
        if($result instanceof FullyQualified){
            if(!isset($this->uses[$code])){
                $parts = $result->parts;
                if(count($parts) === 1){ //Replace to global function/constant
                    return $this->resolveGlobal($result) ?? $name;
                }
                $lastPart = array_pop($parts);
                $this->newUses[$code] = new UseUse(new Name(ltrim($code, "\\")), $lastPart === $originalName ? null : new Node\Identifier($originalName), Use_::TYPE_NORMAL);
                return new Name($originalName, $name->getAttributes());
            }
        }else{
            return $this->resolveGlobal($result) ?? $name;
        }
        return $name; //Return original name instead of resolved name
    }

    /**
     * @param Node[] $nodes
     *
     * @return void
     **/
    private function registerUses(array $nodes) : void{
        foreach($nodes as $node){
            if($node instanceof Use_ || $node instanceof GroupUse){
                foreach($node->uses as $k => $use){
                    $this->uses[$this->getFullyQualifiedString($use, $node, false)] = $use;
                }
            }

            //Child node with recursion processing
            if(isset($node->stmts) && is_array($node->stmts)){
                $this->registerUses($node->stmts);
            }
        }
    }

    /**
     * @param Node[] $nodes
     *
     * @return void
     **/
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

    /**
     * @param Name $name
     *
     * @return Name
     */
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