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

use ref\bundle\traits\SelfFactoryTrait;
use PhpParser\Node;

use RuntimeException;

use function gettype;
use function is_array;
use function is_string;
use function preg_replace;

abstract class Printer{
    use SelfFactoryTrait;

    public const PRINTER_STANDARD = "standard";

    /** @param Node[] $stmts */
    public abstract function printStmts(array $stmts) : string;

    public abstract function printCode(string $code) : string;

    /** @param Node[]|string $value */
    public function print(array|string $value) : string{
        if(is_array($value)){
            $code = $this->printStmts($value);
        }elseif(is_string($value)){
            $code = $this->printCode(preg_replace("/^<\\?php\\s+/", "", $value));
        }else{
            throw new RuntimeException("Argument 1 passed must be of the Node[] or string, " . gettype($value) . " given");
        }

        return "<?php " . $code;
    }

    final public static function registerDefaults() : void{
        self::$defaultKey = self::PRINTER_STANDARD;
        self::register(self::PRINTER_STANDARD, new StandardPrinter());
    }
}