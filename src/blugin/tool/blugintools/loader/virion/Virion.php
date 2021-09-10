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
use blugin\tool\blugintools\builder\Builder;
use pocketmine\Server;

use function dirname;
use function file_get_contents;
use function is_array;
use function is_dir;
use function is_file;
use function strlen;
use function substr;
use function yaml_parse;

class Virion{
    public const INFECTION_FILE = "virus-infections.json";

    private string $name;

    private string $version;

    private string $antigen;

    private string $path;

    private array $yml;
    private array $options;

    public function __construct(string $path, array $yml, array $virionOption = []){
        $this->path = $path;
        $this->yml = $yml;
        $this->options = $virionOption;

        $this->name = $yml["name"];
        $this->version = $yml["version"];
        $this->antigen = $yml["antigen"];
    }

    public function getName() : string{
        return $this->name;
    }

    public function getVersion() : string{
        return $this->version;
    }

    public function getAntigen() : string{
        return $this->antigen;
    }

    public function getPath() : string{
        return $this->path;
    }

    public function getYml() : array{
        return $this->yml;
    }

    public function getOptions() : array{
        return $this->options;
    }

    public static function from(string $path) : ?Virion{
        if(is_dir($path)){
            $path = BluginTools::cleanDirName($path);
        }elseif(is_file($path) && substr($path, -5) === ".phar"){
            $path = "phar://" . BluginTools::cleanDirName($path);
        }else{
            return null;
        }

        $virionYml = "{$path}virion.yml";
        if(!is_file($virionYml))
            return null;

        $data = yaml_parse(file_get_contents($virionYml));
        if(!is_array($data)){
            Server::getInstance()->getLogger()->error("Could not load virion: Error parsing $virionYml");
            return null;
        }

        $name = $data["name"] ?? "";
        foreach(["name", "version", "antigen"] as $requiredAttribute){
            if(!isset($data[$requiredAttribute])){
                Server::getInstance()->getLogger()->error("Could not load virion '$name': Attribute '$requiredAttribute' missing in $virionYml");
                return null;
            }
        }

        return new Virion($path, $data, self::getVirionOptions($path));
    }

    public static function getVirionOptions(string $path, string $projectPath = "") : array{
        if(is_file($file = $path . ".poggit.yml") && is_array($manifest = yaml_parse(file_get_contents($file)))){
            foreach(($manifest["projects"] ?? []) as $projectOption){
                if(BluginTools::cleanDirName(($projectOption["path"] ?? "")) === BluginTools::cleanDirName($projectPath)){
                    return $projectOption["libs"] ?? [];
                }
            }
        }elseif(empty($projectPath)){
            if(is_file($file = $path . Builder::OPTION_FILE) && is_array($manifest = yaml_parse(file_get_contents($file)))){
                $manifest = yaml_parse(file_get_contents($file));
                return $manifest["virion"] ?? [];
            }else{
                return self::getVirionOptions($parentDir = BluginTools::cleanDirName(dirname($path)), substr($path, strlen($parentDir)));
            }
        }
        return [];
    }
}
