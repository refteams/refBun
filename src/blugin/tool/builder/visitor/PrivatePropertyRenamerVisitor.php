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
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;

class PrivatePropertyRenamerVisitor extends RenamerHolderVisitor{
    /** @var PropertyProperty[] */
    private $privateProperties = [];

    /**
     * Register private property on before traverse
     *
     * @param Node[] $nodes
     *
     * @return array
     **/
    public function beforeTraverse(array $nodes){
        $this->renamer->init();
        $this->privateProperties = [];
        $this->registerPrivateProperties($nodes);

        return $nodes;
    }

    /**
     * Register private property with recursion
     *
     * @param Node[] $nodes
     *
     * @return void
     **/
    private function registerPrivateProperties(array $nodes) : void{
        foreach($nodes as $node){
            if($node instanceof Property && $node->isPrivate()){
                foreach($node->props as $prop){
                    $this->privateProperties[] = $prop;
                    $this->generate($prop);
                }
            }

            //Child node with recursion processing
            if(isset($node->stmts) && is_array($node->stmts)){
                $this->registerPrivateProperties($node->stmts);
            }
        }
    }

    /**
     * @param Node $node
     *
     * @return Node
     */
    public function getTarget(Node $node) : Node{
        if($node instanceof PropertyProperty || $node instanceof PropertyFetch){
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
    public function isValidToGenerate(Node $node, string $property = "name") : bool{
        return parent::isValidToRename($node, $property) && $node instanceof PropertyProperty && in_array($node, $this->privateProperties);
    }

    /**
     * @param Node   $node
     * @param string $property
     *
     * @return bool
     */
    public function isValidToRename(Node $node, string $property = "name") : bool{
        return parent::isValidToRename($node, $property) && ($node instanceof PropertyProperty || $node instanceof PropertyFetch);
    }
}