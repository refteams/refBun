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
use PhpParser\NodeVisitorAbstract;

/**
 * This trait override most methods in the {@link NodeVisitorAbstract} abstract class for implements IRenamerHolder interface.
 */
trait RenamerHolderTrait{
    /** @var Renamer */
    protected $renamer;

    /** @param Renamer $renamer */
    public function __construct(Renamer $renamer){
        $this->setRenamer($renamer);
    }

    /** @return Renamer */
    public function getRenamer() : Renamer{
        return $this->renamer;
    }

    /** @param Renamer $renamer */
    public function setRenamer(Renamer $renamer) : void{
        $this->renamer = $renamer;
    }

    /**
     * Reset name table on before traverse
     *
     * @param array $nodes
     *
     * @return Node[]|null
     */
    public function beforeTraverse(array $nodes){
        $this->renamer->init();
        return null;
    }

    /**
     * Register new name of variable on enter traverse
     *
     * @param Node $node
     *
     * @return Node|null
     */
    public function enterNode(Node $node){
        $this->generate($node);
        return null;
    }

    /**
     * Rename variable on leave traverse
     *
     * @param Node $node
     *
     * @return Node|null
     */
    public function leaveNode(Node $node){
        $this->rename($node);
        return null;
    }

    /**
     * @param Node   $node
     * @param string $property = "name"
     */
    public function generate(Node $node, string $property = "name") : void{
        if($this->isValidToGenerate($node, $property))
            $this->renamer->generate($this->getTarget($node), $property);
    }

    /**
     * @param Node   $node
     * @param string $property = "name"
     *
     * @return Node|null
     */
    public function rename(Node $node, string $property = "name") : ?Node{
        if($this->isValidToRename($node, $property))
            return $this->renamer->rename($this->getTarget($node));
        return null;
    }

    /**
     * @param Node $node
     *
     * @return Node
     */
    protected function getTarget(Node $node) : Node{
        return $node;
    }

    /**
     * @param Node   $node
     * @param string $property
     *
     * @return bool
     */
    protected function isValid(Node $node, string $property = "name") : bool{
        $target = $this->getTarget($node);
        return isset($target->$property) && is_string($target->$property);
    }

    /**
     * @param Node   $node
     * @param string $property
     *
     * @return bool
     */
    protected function isValidToGenerate(Node $node, string $property = "name") : bool{
        return $this->isValid($node, $property);
    }

    /**
     * @param Node   $node
     * @param string $property
     *
     * @return bool
     */
    protected function isValidToRename(Node $node, string $property = "name") : bool{
        return $this->isValid($node, $property);
    }
}