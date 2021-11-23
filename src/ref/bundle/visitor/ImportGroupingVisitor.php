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
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeVisitorAbstract;

use function array_keys;
use function array_pop;
use function array_unshift;
use function count;
use function is_array;

class ImportGroupingVisitor extends NodeVisitorAbstract{
    use GetFullyQualifiedTrait;

    /**
     * @param Node[] $nodes
     *
     * @return Node[]|null
     **/
    public function afterTraverse(array $nodes) : ?array{
        /** @var UseUse[][] type => UseUSe[] */
        $usesList = [
            Use_::TYPE_UNKNOWN => [],
            Use_::TYPE_NORMAL => [],
            Use_::TYPE_FUNCTION => [],
            Use_::TYPE_CONSTANT => []
        ];
        $this->readUses($nodes, $usesList);
        $this->writeUses($nodes, $usesList);
        return $nodes;
    }

    /**
     * @param Node[]      &$nodes
     * @param UseUse[][]  &$usesList
     */
    private function readUses(array &$nodes, array &$usesList) : void{
        $keys = array_keys($nodes);
        for($i = 0, $count = count($keys); $i < $count; ++$i){
            $node = $nodes[$keys[$i]];
            if($node instanceof Use_ || $node instanceof GroupUse){
                foreach($node->uses as $use){
                    //Re-create for reset use type
                    $type = $use->type !== 0 ? $use->type : $node->type;
                    $newUse = new UseUse($this->getFullyQualifiedName($use, $node), $use->alias);
                    $usesList[$type][] = $newUse;
                }
                //Remove old use node
                unset($nodes[$keys[$i]]);
            }

            //Child node with recursion processing
            if(isset($node->stmts) && is_array($node->stmts)){
                $this->readUses($node->stmts, $usesList);
            }
        }
    }

    /**
     * @param Node[]     $nodes
     * @param UseUse[][] $usesList
     *
     * @return void
     **/
    private function writeUses(array $nodes, array $usesList) : void{
        foreach($nodes as $node){
            if($node instanceof Namespace_){
                /** @var GroupUse[] $groups group name => GroupUse */
                $groups = [];
                $classUses = $usesList[Use_::TYPE_NORMAL];
                $usesList[Use_::TYPE_NORMAL] = [];
                foreach($classUses as $use){
                    if(count($use->name->parts) === 1){ //Pass root class use
                        $usesList[Use_::TYPE_NORMAL][] = $use;
                        continue;
                    }

                    $useName = new Name(array_pop($use->name->parts));
                    $groupName = $use->name->toCodeString();
                    if(!isset($groups[$groupName])){
                        $groups[$groupName] = new GroupUse($use->name, [], $use->type);
                    }
                    $groups[$groupName]->uses[] = new UseUse($useName, $use->alias, $use->type);
                }
                foreach($usesList as $type => $uses){
                    if(!empty($uses)){
                        array_unshift($node->stmts, new Use_($uses, $type));
                    }
                }
                foreach($groups as $group){
                    if(count($group->uses) === 1){
                        $use = $group->uses[0];
                        array_unshift($node->stmts, new Use_([new UseUse($this->getFullyQualifiedName($use, $group), $use->alias, $use->type)]));
                        continue;
                    }

                    array_unshift($node->stmts, $group);
                }
                return;
            }

            //Child node with recursion processing
            if(isset($node->stmts) && is_array($node->stmts)){
                $this->writeUses($node->stmts, $usesList);
            }
        }
    }
}