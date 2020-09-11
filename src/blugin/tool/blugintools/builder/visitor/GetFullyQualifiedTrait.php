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

namespace blugin\tool\blugintools\builder\visitor;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\UseUse;

trait GetFullyQualifiedTrait{
    /**
     * @param UseUse    $use
     * @param Node|null $node
     *
     * @return FullyQualified
     */
    protected function getFullyQualified(UseUse $use, Node $node = null) : FullyQualified{
        return new FullyQualified($node instanceof GroupUse && $node->prefix ? Name::concat($node->prefix, $use->name) : $use->name, $use->getAttributes());
    }

    /**
     * @param UseUse    $use
     * @param Node|null $node
     *
     * @param bool      $ltrim
     *
     * @return string
     */
    protected function getFullyQualifiedString(UseUse $use, Node $node = null, bool $ltrim = true) : string{
        $str = $this->getFullyQualified($use, $node)->toCodeString();
        return $ltrim ? ltrim($str, "\\") : $str;
    }

    /**
     * @param UseUse    $use
     * @param Node|null $node
     *
     * @param bool      $ltrim
     *
     * @return Name
     */
    protected function getFullyQualifiedName(UseUse $use, Node $node = null, bool $ltrim = true) : Name{
        return new Name($this->getFullyQualifiedString($use, $node, $ltrim));
    }
}