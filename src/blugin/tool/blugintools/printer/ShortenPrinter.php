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

namespace blugin\tool\blugintools\printer;

use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;

class ShortenPrinter extends StandardPrinter{
    public function __construct(){
        $this->standard = new class extends Standard{
            /** @override for prevent indenting and linebreak */
            protected function resetState(){
                $this->nl = " ";
                $this->origTokens = null;
            }

            /** @override for prevent indenting */
            protected function setIndentLevel(int $level){ }

            protected function indent(){ }

            protected function outdent(){ }
        };
    }

    /** @param Node[] $stmts */
    public function print(array $stmts) : string{
        return preg_replace('/^<\?php[\s\n]+/', "<?php ", parent::print($stmts));
    }
}