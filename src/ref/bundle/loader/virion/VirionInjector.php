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

namespace ref\bundle\loader\virion;

use pocketmine\Server;
use pocketmine\utils\TextFormat as C;
use ref\bundle\refBun;

use function array_values;
use function ceil;
use function copy;
use function dirname;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function is_file;
use function json_decode;
use function json_encode;
use function mkdir;
use function str_repeat;
use function strlen;
use function substr;
use function token_get_all;

class VirionInjector{
    public static function injectAll(string $dir, string $namespace, ?array $virionOptions = null) : void{
        static $deep = -1;
        $deep++;

        $dir = refBun::cleanDirName($dir);
        $namespace = refBun::cleaNamespace($namespace);
        $virionOptions ??= Virion::getVirionOptions($dir);
        $virionLoader = VirionLoader::getInstance();
        $virionOptions = self::filteredVirionOptions($dir, $virionOptions);
        if(!empty($virionOptions) && $deep === 0){
            Server::getInstance()->getLogger()->debug(C::DARK_GRAY . "  Virion injected into " . C::GRAY . $namespace);
        }
        foreach($virionOptions as $virionOption){
            [$ownerName, $repoName, $virionName] = explode("/", $virionOption["src"]);
            $virion = $virionLoader->getVirion($virionName);
            if($virion === null){
                Server::getInstance()->getLogger()->info(C::DARK_GRAY . "    Download virion '$virionName' from poggit.pmmp.io");
                $virion = VirionDownloader::download($ownerName, $repoName, $virionName, $virionOption["version"]);
                if($virion === null){
                    Server::getInstance()->getLogger()->error("Could not infect virion '{$virionOption["src"]}': Undefined virion");
                    continue;
                }
                $virionLoader->register($virion);
            }
            $antigen = $virion->getAntigen();
            if(self::inject($dir, $antibody = $namespace . "libs\\$antigen", $virion, $antibodyDir = $deep === 0 ? "src/$antibody" : "libs/$antigen")){
                Server::getInstance()->getLogger()->debug(
                    C::DARK_GRAY . str_repeat("\t", $deep + 1) . "*" .
                    C::GRAY . $virionName . str_repeat("\t", (int) (5 - $deep - ceil((strlen($virionName) + 2) / 8))) . "(" .
                    C::DARK_GRAY . $namespace . C::GRAY . "libs\\$antigen)");

                self::injectAll($dir . $antibodyDir, $antibody, $virion->getOptions());
                self::infectAll($dir, $antibody, $virion);
            }
        }
        $deep--;
    }

    public static function inject(string $dir, string $antibody, Virion $virion, string $antibodyDir) : bool{
        if(!file_exists($dir)){
            mkdir($dir, 0777, true);
        }
        $antigen = $virion->getAntigen();
        $infections = file_exists($infectionsPath = $dir . Virion::INFECTION_FILE) ? json_decode(file_get_contents($infectionsPath), true) : [];
        foreach($infections as $log){
            if($antigen === $log["antigen"]){
                Server::getInstance()->getLogger()->info(C::DARK_GRAY . "   Could not infect virion '" . $virion->getName() . "': Already infected");
                return false;
            }
        }

        $infections[$antibody] = $virion->getYml();
        file_put_contents($infectionsPath, json_encode($infections));

        $antigenPath = refBun::cleanDirName("src/$antigen");
        foreach(refBun::readDirectory($virionPath = $virion->getPath(), true) as $path){
            $innerPath = substr($path, strlen($virionPath));

            if(str_starts_with($innerPath, "resources/")){
                $newPath = $dir . $innerPath;
            }elseif(str_starts_with($innerPath, $antigenPath)){
                $newPath = refBun::cleanDirName($dir . $antibodyDir) . substr($innerPath, strlen($antigenPath));
            }else{
                continue;
            }

            $newDir = dirname($newPath);
            if(!file_exists($newDir)){
                mkdir($newDir, 0777, true);
            }
            copy($path, $newPath);
        }
        return true;
    }

    public static function infectAll(string $dir, string $antibody, Virion $virion) : void{
        $antigen = $virion->getAntigen();
        foreach(refBun::readDirectory($dir, true) as $path){
            if(!is_file($path) || !str_ends_with($path, ".php")){
                continue;
            }

            $contents = self::infect(file_get_contents($path), $antigen, $antibody);

            if(!empty($contents)){
                file_put_contents($path, $contents);
            }
        }
    }

    public static function infect(string $chromosome, string $antigen, string $antibody) : string{
        $tokens = token_get_all($chromosome);
        $tokens[] = "";
        foreach($tokens as $offset => $token){
            if(!is_array($token) || $token[0] !== T_WHITESPACE){
                [$id, $str, $line] = is_array($token) ? $token : [-1, $token, $line ?? 1];
                if(isset($init, $current, $prefixToken)){
                    if(($current === "" && $prefixToken === T_USE && $id === T_FUNCTION) || $id === T_CONST){
                        continue;
                    }

                    if($id === T_NS_SEPARATOR || $id === T_STRING){
                        $current .= $str;
                    }elseif(!(($current === "" && $prefixToken === T_USE && $id === T_FUNCTION) || $id === T_CONST)){
                        if(str_starts_with($current, $antigen)){ // case-sensitive!
                            $new = $antibody . substr($current, strlen($antigen));
                            for($o = $init + 1; $o < $offset; ++$o){
                                if($tokens[$o][0] === T_NS_SEPARATOR || $tokens[$o][0] === T_STRING){
                                    $tokens[$o][1] = $new;
                                    $new = "";
                                }
                            }
                        }
                        unset($init, $current, $prefixToken);
                    }
                }elseif($id === T_NS_SEPARATOR || $id === T_NAMESPACE || $id === T_USE){
                    $init = $offset;
                    $current = "";
                    $prefixToken = $id;
                }
            }
        }
        $ret = "";
        foreach($tokens as $token){
            $ret .= is_array($token) ? $token[1] : $token;
        }

        return $ret;
    }

    public static function filteredVirionOptions(string $path, array $virionOptions) : array{
        $virionLoader = VirionLoader::getInstance();
        $infections = file_exists($infectionsPath = $path . Virion::INFECTION_FILE) ? json_decode(file_get_contents($infectionsPath), true) : [];
        foreach($virionOptions as $key => $virionOption){
            $virion = $virionLoader->getVirion(explode("/", $virionOption["src"])[2]);
            if($virion === null){
                continue;
            }

            foreach($infections as $log){
                if($virion->getAntigen() === $log["antigen"]){
                    unset($virionOptions[$key]);
                }
            }
        }
        return array_values($virionOptions);
    }
}