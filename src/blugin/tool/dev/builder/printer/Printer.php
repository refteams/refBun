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

namespace blugin\tool\dev\builder\printer;

use blugin\tool\dev\builder\AdvancedBuilder;
use PhpParser\Node;

abstract class Printer{
    public const PRINTER_STANDARD = "standard";
    public const PRINTER_SHORTEN = "shorten";

    /** @param Node[] $stmts */
    public abstract function print(array $stmts) : string;

    final public static function registerDefaults() : void{
        $builder = AdvancedBuilder::getInstance();
        $builder->registerPrinter(self::PRINTER_STANDARD, new StandardPrinter());
        $builder->registerPrinter(self::PRINTER_SHORTEN, new ShortenPrinter());
    }
}