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

namespace blugin\tool\blugintools\visitor;

use blugin\utils\arrays\ArrayUtil as Arr;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class CommentOptimizingVisitor extends NodeVisitorAbstract{
    /** string[] regex format[] */
    private static $allowList = null;

    public function enterNode(Node $node) : ?Node{
        $doc = $node->getDocComment();

        //Remove all comments
        $node->setAttribute("comments", []);

        //If the comment has no doc comment, skip.
        if($doc === null)
            return null;

        //Store meaningfull comments
        $docText = $doc->getText();
        $docComments = Arr::from(self::getAllowList())
            ->map(function(string $regex) use ($docText): array{ return preg_match($regex, $docText, $matches) ? $matches : []; })
            ->filter(function(array $matches) : bool{ return !empty($matches); })
            ->map(function(array $matches) : string{ return implode(" ", array_slice($matches, 1)); });

        //If the comment has no meaningfull comments, skip.
        if($docComments->count() === 0)
            return null;

        //Add doc comment
        $node->setAttribute("comments", [new Doc($docComments->join(PHP_EOL . "* @", "/**", PHP_EOL . "*/"))]);
        return $node;
    }

    /** @return string[] regex format[] */
    public static function getAllowList() : array{
        if(self::$allowList === null){
            self::initMeaningfullList();
        }
        return self::$allowList;
    }

    /** @param string $regex */
    public static function register(string $regex) : void{
        if(self::$allowList === null){
            self::initMeaningfullList();
        }
        self::$allowList[] = $regex;
    }

    public static function initMeaningfullList() : void{
        self::$allowList = [];
        self::register("/^[\t ]*\* @(notHandler)/m");
        self::register("/^[\t ]*\* @(ignoreCancelled)/m");
        self::register("/^[\t ]*\* @(handleCancelled)/m");
        self::register("/^[\t ]*\* @(softDepend)[\t ]+([a-zA-Z]+)/m");
        self::register("/^[\t ]*\* @(priority)[\t ]+([a-zA-Z]+)/m");
    }
}