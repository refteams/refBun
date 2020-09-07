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

namespace blugin\tool\dev\builder;

use blugin\tool\dev\BluginTools;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\ParserFactory;

final class CodeSpliter{
    private function __contruct(){
    }

    /**
     * @param Node[] $nodes
     * @param string $mainName
     *
     * @return Node[][] filename => Node[]
     */
    public static function splitNodes(array $nodes, string $mainName) : array{
        $result = [$mainName => $nodes];
        $classLikes = null;
        foreach($nodes as $node){
            if($node instanceof Namespace_){
                $classLikes = [];
                for($i = 0, $keys = array_keys($node->stmts), $count = count($node->stmts); $i < $count; ++$i){
                    $key = $keys[$i];
                    $child = $node->stmts[$key];
                    if($child instanceof ClassLike && $child->name !== null && $child->name->name !== $mainName){
                        $classLikes[] = $child;
                        unset($node->stmts[$key]);
                    }
                }
                break;
            }

            //Child node with recursion processing
            if(isset($node->stmts) && is_array($node->stmts)){
                return self::splitNodes($node->stmts, $mainName);
            }
        }
        if($classLikes !== null){
            $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
            $printer = BluginTools::getInstance()->getBuilder()->getPrinter(AdvancedBuilder::PRINTER_STANDARD);
            foreach($classLikes as $classLike){
                //TODO: Deep copy implement instead of re-parse trick
                //TODO: Remove unused imports
                $cloneNodes = $parser->parse($printer->print($nodes));
                $replacedNodes = self::replaceClassLike($cloneNodes, $classLike);
                if($replacedNodes !== null){
                    $result[$classLike->name->name] = $replacedNodes;
                }
            }
        }
        return $result;
    }

    /**
     * @param array     $nodes
     * @param ClassLike $replacement
     *
     * @return array|null
     */
    private static function replaceClassLike(array $nodes, ClassLike $replacement) : ?array{
        foreach($nodes as $node){
            if($node instanceof Namespace_){
                for($i = 0, $keys = array_keys($node->stmts), $count = count($node->stmts); $i < $count; ++$i){
                    $key = $keys[$i];
                    $child = $node->stmts[$key];
                    if($child instanceof ClassLike){
                        $node->stmts[$key] = $replacement;
                        return $nodes;
                    }
                }
                break;
            }

            //Child node with recursion processing
            if(isset($node->stmts) && is_array($node->stmts)){
                if(self::replaceClassLike($node->stmts, $replacement) !== null){
                    return $nodes;
                }
            }
        }
        return null;
    }
}
