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
 *
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace ref\bundle\traverser;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use ref\bundle\traits\SelfFactoryTrait;
use ref\bundle\traverser\TraverserPriority as Priority;

class Traverser extends NodeTraverser{
    use SelfFactoryTrait;

    /** @var NodeVisitor[] Visitors */
    public function getVisitors() : array{
        return $this->visitors;
    }

    public function removeVisitors() : void{
        $this->visitors = [];
    }

    final public static function registerDefaults() : void{
        foreach(Priority::ALL as $priority){
            self::register($priority, new Traverser());
        }
    }

    public static function registerVisitor(int $priority, NodeVisitorAbstract $visitor) : bool{
        $traverser = self::get($priority);
        if($traverser === null){
            return false;
        }

        $traverser->addVisitor($visitor);
        return true;
    }
}