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

abstract class RenamerHolderVisitor extends NodeVisitorAbstract{
    protected $renamer = null;

    public function __construct(Renamer $renamer){
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
        $this->renamer->generate($node);
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
        $this->renamer->rename($node);
        return null;
    }
}