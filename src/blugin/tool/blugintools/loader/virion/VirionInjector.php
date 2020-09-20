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

namespace blugin\tool\blugintools\loader\virion;

use blugin\tool\blugintools\BluginTools;
use pocketmine\Server;

class VirionInjector{
    public static function injectAll(string $dir, string $namespace, ?array $virionOptions = null) : void{
        $namespace = BluginTools::cleaNamespace($namespace);
        $virionOptions = $virionOptions ?? Virion::getVirionOptions($dir);
        $virionLoader = VirionLoader::getInstance();
        foreach(self::filteredVirionOptions($dir, $virionOptions) as $virionOption){
            [$ownerName, $repoName, $virionName] = explode("/", $virionOption["src"]);
            $virion = $virionLoader->getVirion($virionName);
            if($virion === null){
                Server::getInstance()->getLogger()->info("Download virion '$virionName' from poggit.pmmp.io");
                $virion = VirionDownloader::download($ownerName, $repoName, $virionName, $virionOption["version"]);
                if($virion === null){
                    Server::getInstance()->getLogger()->error("Could not infect virion '{$virionOption["src"]}': Undefined virion");
                    continue;
                }
                $virionLoader->register($virion);
            }
            if(self::inject($dir, $antibody = $namespace . "libs\\" . $virion->getAntigen(), $virion)){
                self::injectAll($dir, $antibody, $virion->getOptions());
            }
        }
    }

    public static function inject(string $dir, string $antibody, Virion $virion) : bool{
        Server::getInstance()->getLogger()->info($dir);
        $antigen = $virion->getAntigen();
        $infections = file_exists($infectionsPath = $dir . Virion::INFECTION_FILE) ? json_decode(file_get_contents($infectionsPath), true) : [];
        foreach($infections as $log){
            if($antigen === $log["antigen"]){
                Server::getInstance()->getLogger()->error("Could not infect virion '" . $virion->getName() . "': Already infected");
                return false;
            }
        }

        $infections[$antibody] = $virion->getYml();
        file_put_contents($infectionsPath, json_encode($infections));

        foreach(BluginTools::readDirectory($dir, true) as $path){
            if(!is_file($path) || substr($path, -4) !== ".php")
                continue;

            $contents = self::infect(file_get_contents($path), $antigen, $antibody);
            if(!empty($contents)){
                file_put_contents($path, $contents);
            }
        }

        foreach(BluginTools::readDirectory($virionPath = $virion->getPath(), true) as $path){
            $innerPath = substr($path, strlen($virionPath));

            if(strpos($innerPath, "resources/") === 0){
                $newPath = $dir . $innerPath;
                $newDir = dirname($newPath);
                if(!file_exists($newDir)){
                    mkdir($newDir, 0777, true);
                }

                copy($path, $newPath);
            }elseif(strpos($innerPath, $antigenPath = BluginTools::cleanDirName("src/$antigen")) === 0){
                $newPath = substr_replace($path, $dir . BluginTools::cleanDirName("src/$antibody"), 0, strlen($virionPath . $antigenPath));
                $newDir = dirname($newPath);
                if(!file_exists($newDir)){
                    mkdir($newDir, 0777, true);
                }

                $contents = self::infect(file_get_contents($path), $antigen, $antibody);
                if(!empty($contents)){
                    file_put_contents($newPath, $contents);
                }
            }
        }
        return true;
    }

    public static function infect(string $chromosome, string $antigen, string $antibody) : string{
        $tokens = token_get_all($chromosome);
        $tokens[] = "";
        foreach($tokens as $offset => $token){
            if(!is_array($token) or $token[0] !== T_WHITESPACE){
                list($id, $str, $line) = is_array($token) ? $token : [-1, $token, $line ?? 1];
                if(isset($init, $current, $prefixToken)){
                    if($current === "" && $prefixToken === T_USE and $id === T_FUNCTION || $id === T_CONST){
                        continue;
                    }elseif($id === T_NS_SEPARATOR || $id === T_STRING){
                        $current .= $str;
                    }elseif(!($current === "" && $prefixToken === T_USE and $id === T_FUNCTION || $id === T_CONST)){
                        if(strpos($current, $antigen) === 0){ // case-sensitive!
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
                }else{
                    if($id === T_NS_SEPARATOR || $id === T_NAMESPACE || $id === T_USE){
                        $init = $offset;
                        $current = "";
                        $prefixToken = $id;
                    }
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
            if($virion === null)
                continue;

            foreach($infections as $log){
                if($virion->getAntigen() === $log["antigen"]){
                    unset($virionOptions[$key]);
                }
            }
        }
        return array_values($virionOptions);
    }
}