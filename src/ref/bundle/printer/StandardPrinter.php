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

namespace ref\bundle\printer;

use ref\bundle\builder\Builder;
use PhpParser\Node\Stmt;
use PhpParser\PrettyPrinter\Standard;

class StandardPrinter extends Printer{
    protected Standard $standard;

    public function __construct(){
        $this->standard = new Standard();
    }

    public function getStandard() : Standard{
        return $this->standard;
    }

    /** @param Stmt[] $stmts */
    public function printStmts(array $stmts) : string{
        return $this->standard->prettyPrint($stmts);
    }

    public function printCode(string $code) : string{
        return $this->printStmts(Builder::parse($code));
    }
}