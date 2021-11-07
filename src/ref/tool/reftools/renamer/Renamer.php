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

namespace ref\tool\reftools\renamer;

use ref\tool\reftools\traits\SelfFactoryTrait;
use PhpParser\Node;

use function in_array;
use function str_replace;
use function strcasecmp;

abstract class Renamer{
    use SelfFactoryTrait;

    public const RENAMER_SHORTEN = "shorten";
    public const RENAMER_SERIAL = "serial";
    public const RENAMER_SPACE = "space";
    public const RENAMER_MD5 = "md5";

    public const FLAG_IGNORECASE = 0b00000001;    //It means that the visitor ignores case in name
    public const FLAG_ALLOW_SLASH = 0b00000010;   //It means that the visitor allow slash in name
    public const FLAG_INITIAL_VALID = 0b00000100; //It means that the visitor require valid of initial letter

    /** @var string[] original name => new name */
    private array $nameTable = [];

    private int $flags = 0 | self::FLAG_INITIAL_VALID;

    public function init() : void{
        $this->nameTable = [];
    }

    public abstract function generate(Node $node, string $property = "name") : void;

    public function rename(Node $node, string $property = "name") : ?Node{
        $newName = $this->nameTable[$node->$property] ?? null;
        if(!$newName)
            return null;

        $node->$property = $newName;
        return $node;
    }

    /** @return string[] */
    public function getNameTable() : array{
        return $this->nameTable;
    }

    public function getName(string $name) : ?string{
        return $this->nameTable[$name] ?? null;
    }

    public function setName(string $name, string $newName) : void{
        $this->nameTable[$name] = $newName;
    }

    public function getFlags() : int{
        return $this->flags;
    }

    public function isIgnorecase() : bool{
        return ($this->flags & self::FLAG_IGNORECASE) !== 0;
    }

    public function setIgnorecase(bool $value = true) : void{
        if($value){
            $this->flags |= self::FLAG_IGNORECASE;
        }else{
            $this->flags &= ~self::FLAG_IGNORECASE;
        }
    }

    public function isAllowSlash() : bool{
        return ($this->flags & self::FLAG_ALLOW_SLASH) !== 0;
    }

    public function setAllowSlash(bool $value = true) : void{
        if($value){
            $this->flags |= self::FLAG_ALLOW_SLASH;
        }else{
            $this->flags &= ~self::FLAG_ALLOW_SLASH;
        }
    }

    public function requireInitialValid() : bool{
        return ($this->flags & self::FLAG_INITIAL_VALID) !== 0;
    }

    public function setRequireInitialValid(bool $value = true) : void{
        if($value){
            $this->flags |= self::FLAG_INITIAL_VALID;
        }else{
            $this->flags &= ~self::FLAG_INITIAL_VALID;
        }
    }

    /**
     * in_array with ignore case flag
     *
     * @param $needle
     * @param $array
     *
     * @return bool
     */
    protected function in_array($needle, $array) : bool{
        if($this->isIgnorecase()){
            foreach($array as $key => $value){
                if(strcasecmp($needle, $value) === 0)
                    return true;
            }
            return false;
        }else{
            return in_array($needle, $array);
        }
    }

    protected function clean(string $name) : string{
        if(!$this->isAllowSlash()){
            $name = str_replace(["/", "\\"], "", $name);
        }
        return $name;
    }

    final public static function registerDefaults() : void{
        self::register(self::RENAMER_SHORTEN, new ShortenRenamer());
        self::register(self::RENAMER_SERIAL, new SerialRenamer());
        self::register(self::RENAMER_MD5, new MD5Renamer());
    }
}