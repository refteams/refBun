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

namespace blugin\tool\blugintools\builder;

use blugin\tool\blugintools\BluginTools;
use blugin\utils\arrays\ArrayUtil as Arr;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\ScriptPluginLoader;
use pocketmine\Server;
use pocketmine\utils\TextFormat as C;

class PluginBuildExecutor implements CommandExecutor{
    /** @param string[] $args */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if(empty($args))
            return false;

        if($args[0] === "*"){
            $args = Arr::map(Server::getInstance()->getPluginManager()->getPlugins(), function(Plugin $plugin) : string{ return $plugin->getName(); });
        }
        $count = count($args);
        $sender->sendMessage(C::AQUA . "[PluginBuild] Start build the $count plugins. (create in " . C::DARK_AQUA . BluginTools::cleanDirName(BluginTools::getInstance()->getDataFolder()) . C::AQUA . ")");

        $failures = Arr::from([]);
        $successes = Arr::from($args)
            ->map(function(string $pluginName){ return BluginTools::getPlugin($pluginName) ?? $pluginName; })
            ->filter(function($plugin) use ($sender, $failures) : bool{
                if(!$plugin instanceof PluginBase){
                    $sender->sendMessage(C::DARK_GRAY . " - " . ($pluginName = $plugin) . " is invalid plugin name");
                }elseif($plugin->getPluginLoader() instanceof ScriptPluginLoader){
                    $sender->sendMessage(C::DARK_GRAY . " - " . ($pluginName = $plugin->getName()) . " is script plugin");
                }else{
                    return true;
                }
                $failures[] = $pluginName;
                return false;
            })->map(function(PluginBase $plugin) use ($sender) : string{
                $this->buildPlugin($plugin);

                $sender->sendMessage(C::DARK_GRAY . " + {$plugin->getName()} has been builded to " . self::getPharName($plugin));
                return $plugin->getName();
            });
        $sender->sendMessage(C::AQUA . "[PluginBuild] All plugin builds are complete. " . C::GREEN . "{$successes->count()} successes  " . C::RED . "{$failures->count()} failures");
        $sender->sendMessage(C::AQUA . " - Results ($count): " . $successes->join(", ", C::GREEN, C::RESET . ", ") . $failures->join(", ", C::RED));
        return true;
    }

    /** @throws \ReflectionException */
    public function buildPlugin(PluginBase $plugin) : void{
        static $fileProperty;
        if(!isset($fileProperty)){
            $reflection = new \ReflectionClass(PluginBase::class);
            $fileProperty = $reflection->getProperty("file");
            $fileProperty->setAccessible(true);
        }

        Builder::getInstance()->buildPhar(
            $fileProperty->getValue($plugin),
            BluginTools::loadDir() . self::getPharName($plugin),
            preg_replace("/[a-z_][a-z\d_]*$/i", "", ($main = ($description = $plugin->getDescription())->getMain())),
            [
                "name" => $description->getName(),
                "version" => $description->getVersion(),
                "main" => $main,
                "api" => $description->getCompatibleApis(),
                "depend" => $description->getDepend(),
                "description" => $description->getDescription(),
                "authors" => $description->getAuthors(),
                "website" => $description->getWebsite(),
                "creationDate" => time()
            ]
        );
    }

    public static function getPharName(Plugin $plugin) : string{
        return "{$plugin->getName()}_v{$plugin->getDescription()->getVersion()}.phar";
    }
}
