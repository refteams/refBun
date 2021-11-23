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
use pocketmine\utils\Internet;

use function file_exists;
use function file_put_contents;
use function mkdir;
use function sprintf;

class VirionDownloader{
    public const VIRION_GET_URL = "https://poggit.pmmp.io//v.dl/%s/%s/%s/%s";
    public const FILE_NAME_FORMAT = "%s_v%s.phar";

    public static function download(string $ownerName, string $repoName, string $projectName, string $versionConstraint = "^0.0.1") : ?Virion{
        $url = sprintf(self::VIRION_GET_URL, $ownerName, $repoName, $projectName, $versionConstraint);
        [$body, [$header], $httpCode] = Internet::simpleCurl($url);
        if($httpCode !== 200){
            return null;
        }

        $dir = Server::getInstance()->getDataPath() . "virions/";
        if(!file_exists($dir)){
            mkdir($dir, 0777, true);
        }

        $path = $dir . sprintf(self::FILE_NAME_FORMAT, $projectName, $header["x-poggit-virion-version"] ?? $versionConstraint);
        if(file_put_contents($path, $body) === false){
            return null;
        }

        return Virion::from($path);
    }
}