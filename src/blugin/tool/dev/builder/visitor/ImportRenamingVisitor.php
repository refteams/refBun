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

namespace blugin\tool\dev\builder\visitor;

use blugin\tool\dev\builder\renamer\IRenamerHolder;
use blugin\tool\dev\builder\renamer\Renamer;
use blugin\tool\dev\builder\renamer\RenamerHolderTrait;
use PhpParser\ErrorHandler;
use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitor\NameResolver;

class ImportRenamingVisitor extends NameResolver implements IRenamerHolder{
    use RenamerHolderTrait, GetFullyQualifiedTrait;

    /**
     * @param Renamer           $renamer
     * @param ErrorHandler|null $errorHandler Error handler
     * @param array             $options Options
     */
    public function __construct(Renamer $renamer, ErrorHandler $errorHandler = null, array $options = []){
        parent::__construct($errorHandler, $options);
        $this->setRenamer($renamer);
    }

    /** @param Renamer $renamer */
    public function setRenamer(Renamer $renamer) : void{
        $this->renamer = $renamer;
        $renamer->setIgnorecase();
    }

    /**
     * Register namespaces
     *
     * @param Node[] $nodes
     *
     * @return array
     **/
    public function beforeTraverse(array $nodes){
        $this->nameContext->startNamespace();
        $this->getRenamer()->init();
        $this->registerUses($nodes);
        return $nodes;
    }

    /**
     * @param Node $node
     *
     * @return int|Node|null
     */
    public function enterNode(Node $node){
        if($node instanceof FunctionLike){
            foreach($node->getParams() as $key => $param){
                if($param->default instanceof ConstFetch && $param->default->name->parts[0] === "null"){
                    $param->default->setAttribute("byParams", $param);
                }
            }
        }
        if($node instanceof ConstFetch && $node->hasAttribute("byParams")){
            return $node;
        }
        return parent::enterNode($node);
    }

    /**
     * @param Node $node
     *
     * @return int|Node|Node[]|GroupUse|Use_|null
     */
    public function leaveNode(Node $node){
        if($node instanceof Use_ || $node instanceof GroupUse){
            foreach($node->uses as $k => $use){
                $newName = $this->renamer->rename(new Identifier($this->getFullyQualifiedString($use, $node)));
                if($newName instanceof Identifier){
                    $use->alias = $newName;
                }
            }
            return $node;
        }
        return null;
    }

    /**
     * @param Name $name
     * @param int  $type
     *
     * @return Name
     */
    protected function resolveName(Name $name, int $type) : Name{
        $result = parent::resolveName($name, $type);
        if(!$this->replaceNodes)
            return $result;

        $newName = $this->renamer->rename(new Identifier(ltrim($result->toCodeString(), "\\")));
        if($newName instanceof Identifier)
            return new Name($newName->name, $result->getAttributes());
        return $result;
    }

    /**
     * Register namespaces nodes with recursion
     *
     * @param Node[] $nodes
     *
     * @return void
     **/
    private function registerUses(array $nodes) : void{
        foreach($nodes as $node){
            if($node instanceof Use_ || $node instanceof GroupUse){
                foreach($node->uses as $k => $use){
                    $this->renamer->generate(new Identifier($this->getFullyQualifiedString($use, $node)));
                }
            }

            //Child node with recursion processing
            if(isset($node->stmts) && is_array($node->stmts)){
                $this->registerUses($node->stmts);
            }
        }
    }
}