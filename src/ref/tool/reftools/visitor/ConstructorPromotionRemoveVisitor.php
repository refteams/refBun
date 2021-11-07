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
 * @noinspection PhpDocSignatureInspection
 */

declare(strict_types=1);

namespace ref\tool\reftools\visitor;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\NodeVisitorAbstract;

use function array_unshift;

class ConstructorPromotionRemoveVisitor extends NodeVisitorAbstract{
    /**
     * @return null|int|Node|Node[] Replacement node (or special return value)
     */
    public function leaveNode(Node $node) : Node|array|null{
        if($node instanceof Node\Stmt\ClassMethod && $node->name->toLowerString() === "__construct"){
            $nodes = [];
            foreach($node->params as $param){
                if($param->flags & Class_::VISIBILITY_MODIFIER_MASK){
                    $nodes[] = new Property(
                        $param->flags,
                        [new PropertyProperty($param->var->name)],
                        [],
                        $param->type
                    );
                    array_unshift($node->stmts, new Expression(new Assign(
                        new PropertyFetch(new Variable("this"), $param->var->name),
                        new Variable($param->var->name)
                    )));

                    $param->flags = 0;
                }
            }
            if(!empty($nodes)){
                $nodes[] = $node;
                return $nodes;
            }
        }

        return $node;
    }
}