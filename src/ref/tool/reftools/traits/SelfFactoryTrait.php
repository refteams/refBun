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

namespace ref\tool\reftools\traits;

trait SelfFactoryTrait{
    /** @var self[] */
    protected static array $instances = [];

    protected static string|int $defaultKey = 0;

    /** @return self[] */
    public static function getAll() : array{
        return self::$instances;
    }

    public static function get(string|int $key = null) : ?self{
        return self::$instances[$key] ?? self::$instances[self::$defaultKey] ?? null;
    }

    public static function getClone(string|int $key = null) : ?self{
        $instance = self::get($key);
        return $instance === null ? null : clone $instance;
    }

    /** @param mixed $key */
    public static function register(string|int $key, self $instance) : void{
        self::$instances[$key] = $instance;
    }

    final public static function registerDefaults() : void{
    }
}
