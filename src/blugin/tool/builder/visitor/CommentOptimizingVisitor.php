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

use PhpParser\Comment;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class CommentOptimizingVisitor extends NodeVisitorAbstract{
    /** string[] regex format[] */
    private $meaningfullList = [];

    public function __construct(){
        $this->register("/^[\t ]*\* @(notHandler)/m");
        $this->register("/^[\t ]*\* @(ignoreCancelled)/m");
        $this->register("/^[\t ]*\* @(handleCancelled)/m");
        $this->register("/^[\t ]*\* @(softDepend)[\t ]+([a-zA-Z]+)/m");
        $this->register("/^[\t ]*\* @(priority)[\t ]+([a-zA-Z]+)/m");
    }

    /**
     * Remove meaningless comment
     *
     * @param Node $node
     *
     * @return Node|null
     * @priority HIGHEST
     * @notHandler HIGHEST
     * @ignoreCancelled
     * @notHandler
     * @handleCancelled
     */
    public function enterNode(Node $node){
        $doc = $node->getDocComment();
        if($doc === null)
            return null;

        //Remove existing doc comment
        $comments = array_filter($node->getComments(), function(Comment $comment) : bool{
            return !$comment instanceof Doc;
        });

        //Store meaningfull comments
        $docComments = [];
        $docText = $doc->getText();
        foreach($this->getMeaningfullList() as $_ => $regex){
            if(preg_match($regex, $docText, $matches) > 0){
                $docComments[] = implode(" ", array_slice($matches, 1));
            }
        }

        //If the comment is meaningful, add new doc comment
        if(!empty($docComments)){
            $newText = "/** " . PHP_EOL;
            foreach($docComments as $value){
                $newText .= "* @$value" . PHP_EOL;
            }
            $newText .= "*/";
            $comments[] = new Doc($newText);
        }

        $node->setAttribute('comments', $comments);
        return $node;
    }

    /** @return array */
    public function getMeaningfullList() : array{
        return $this->meaningfullList;
    }

    /** @param string $regex */
    public function register(string $regex) : void{
        $this->meaningfullList[] = $regex;
    }
}