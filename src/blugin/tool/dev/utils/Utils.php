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

namespace blugin\tool\dev\utils;

use pocketmine\plugin\Plugin;
use pocketmine\Server;

class Utils{
    public static function clearDirectory(string $directory) : bool{
        if(!file_exists($directory))
            return mkdir($directory, 0777, true);

        $files = array_diff(scandir($directory), [".", ".."]);
        foreach($files as $file){
            $path = "{$directory}/{$file}";
            if(is_dir($path)){
                self::clearDirectory($path);
                rmdir($path);
            }else{
                unlink($path);
            }
        }
        return (count(scandir($directory)) == 2);
    }

    public static function getPlugin(string $name) : ?Plugin{
        $plugins = Server::getInstance()->getPluginManager()->getPlugins();
        if(isset($plugins[$name]))
            return $plugins[$name];

        $found = null;
        $length = strlen($name);
        $minDiff = PHP_INT_MAX;
        foreach($plugins as $pluginName => $plugin){
            if(stripos($pluginName, $name) === 0){
                $diff = strlen($pluginName) - $length;
                if($diff < $minDiff){
                    $found = $plugin;
                    if($diff === 0)
                        break;

                    $minDiff = $diff;
                }
            }
        }
        return $found;
    }
}
