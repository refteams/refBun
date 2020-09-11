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

namespace blugin\tool\dev\virion;

use blugin\tool\dev\BluginTools;
use pocketmine\Server;

class Virion{
    /** @var string */
    private $name;

    /** @var string */
    private $version;

    /** @var string */
    private $antigen;

    /** @var string */
    private $path;

    /** @var mixed[] */
    private $yml;

    public function __construct(string $path, array $yml){
        $this->path = $path;
        $this->yml = $yml;

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

    /** @return mixed[] */
    public function getYml() : array{
        return $this->yml;
    }

    public static function from(string $path) : ?Virion{
        if(is_dir($path)){
            $path = BluginTools::cleanDirName($path);
        }elseif(is_file($path) && substr($path, -5) === ".phar"){
            $path = "phar://" . BluginTools::cleanDirName($path);
        }else{
            Server::getInstance()->getLogger()->error("Could not load virion: invalid path ($path)");
            return null;
        }

        $virionYml = "{$path}virion.yml";
        if(!is_file($virionYml)){
            Server::getInstance()->getLogger()->error("Could not load virion: virion.yml missing");
            return null;
        }

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

        return new Virion($path, $data);
    }
}
