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

namespace blugin\tool\blugintools\traits\renamer;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

use function is_string;

/**
 * This trait override most methods in the {@link NodeVisitorAbstract} abstract class for implements {@link IRenamerHolder} interface.
 */
trait RenamerHolderVisitorTrait{
    use RenamerHolderTrait;

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

    public function generate(Node $node, string $property = "name") : void{
        if($this->isValidToGenerate($node, $property))
            $this->renamer->generate($this->getTarget($node), $property);
    }

    public function rename(Node $node, string $property = "name") : ?Node{
        if($this->isValidToRename($node, $property))
            return $this->renamer->rename($this->getTarget($node));
        return null;
    }

    protected function getTarget(Node $node) : Node{
        return $node;
    }

    protected function isValid(Node $node, string $property = "name") : bool{
        $target = $this->getTarget($node);
        return isset($target->$property) && is_string($target->$property);
    }

    protected function isValidToGenerate(Node $node, string $property = "name") : bool{
        return $this->isValid($node, $property);
    }

    protected function isValidToRename(Node $node, string $property = "name") : bool{
        return $this->isValid($node, $property);
    }
}