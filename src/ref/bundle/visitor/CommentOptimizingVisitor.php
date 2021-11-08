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

namespace ref\bundle\visitor;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use pocketmine\utils\Utils;

use function array_slice;
use function count;
use function implode;
use function preg_match;

class CommentOptimizingVisitor extends NodeVisitorAbstract{
    /** string[] name => $contentRegex */
    private static ?array $allowTags = null;

    public function enterNode(Node $node) : ?Node{
        $doc = $node->getDocComment();

        //Remove all comments
        $node->setAttribute("comments", []);

        //If the comment has no doc comment, skip.
        if($doc === null)
            return null;

        $allowTags = self::getAllowTags();
        $tags = [];
        foreach(Utils::parseDocComment($doc->getText()) as $name => $content){
            if(!isset($allowTags[$name]))
                continue;

            preg_match($allowTags[$name], $content, $matches);
            $tags[] = "@$name " . implode(" ", array_slice($matches, 1));
        }

        //If the comment has no meaningful comments, skip.
        if(($count = count($tags)) === 0)
            return null;

        if($count === 1){
            $newDocText = "/** $tags[0] */";
        }else{
            $newDocText = "/**" . PHP_EOL . " * ";
            $newDocText .= implode(PHP_EOL . " * ", $tags);
            $newDocText .= PHP_EOL . "*/";
        }
        //Add doc comment
        $node->setAttribute("comments", [new Doc($newDocText)]);
        return $node;
    }

    /** @return string[] regex format[] */
    public static function getAllowTags() : array{
        if(self::$allowTags === null){
            self::initAllowTags();
        }
        return self::$allowTags;
    }

    /**
     * @param string $name
     * @param string $regex
     */
    public static function register(string $name, string $regex = "//") : void{
        if(self::$allowTags === null){
            self::initAllowTags();
        }
        self::$allowTags[$name] = $regex;
    }

    public static function initAllowTags() : void{
        self::$allowTags = [];
        self::register("notHandler");
        self::register("ignoreCancelled");
        self::register("handleCancelled");
        self::register("softDepend", "/([a-zA-Z]+)/");
        self::register("priority", "/([a-zA-Z]+)/");
    }
}