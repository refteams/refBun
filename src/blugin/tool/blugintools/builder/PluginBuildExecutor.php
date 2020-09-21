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
            $args = [];
            foreach(Server::getInstance()->getPluginManager()->getPlugins() as $plugin){
                $args[] = $plugin->getName();
            }
        }
        $count = count($args);
        $sender->sendMessage(C::AQUA . "[PluginBuild] Start build the $count plugins. (create in " . C::DARK_AQUA . BluginTools::cleanDirName(BluginTools::getInstance()->getDataFolder()) . C::AQUA . ")");

        $failures = [];
        $successes = [];
        foreach($args as $pluginName){
            $plugin = BluginTools::getPlugin($pluginName);
            if(!$plugin instanceof PluginBase){
                $failures[] = $pluginName;
                $sender->sendMessage(C::DARK_GRAY . " - " . $pluginName . " is invalid plugin name");
            }elseif($plugin->getPluginLoader() instanceof ScriptPluginLoader){
                $failures[] = $pluginName;
                $sender->sendMessage(C::DARK_GRAY . " - " . ($pluginName = $plugin->getName()) . " is script plugin");
            }else{
                $successes[] = $plugin->getName();
                $this->buildPharPlugin($plugin);
                $sender->sendMessage(C::DARK_GRAY . " + {$plugin->getName()} has been builded to " . self::getPharName($plugin));
            }
        }

        $sender->sendMessage(C::AQUA . "[PluginBuild] All plugin builds are complete. " . C::GREEN . count($successes) . " successes  " . C::RED . count($failures) . " failures");
        $sender->sendMessage(C::AQUA . " - Results ($count): " .
            C::GREEN . implode($successes, ", ",) . C::RESET . ", " .
            C::RED . implode(", ", $failures));
        return true;
    }

    /** @throws \ReflectionException */
    public function buildPharPlugin(PluginBase $plugin) : void{
        CommentOptimizingVisitor::initMeaningfullList();
        Builder::getInstance()->buildPhar(self::getSourcePath($plugin), self::getPharPath($plugin), self::getPluginNamespace($plugin), self::getPluginMetadata($plugin));
    }

    public static function getPharName(Plugin $plugin) : string{
        return self::getPluginFullName($plugin) . ".phar";
    }

    public static function getPharPath(Plugin $plugin) : string{
        return BluginTools::loadDir() . self::getPharName($plugin);
    }

    /** @throws \ReflectionException */
    public static function getSourcePath(Plugin $plugin) : string{
        static $fileProperty;
        if(!isset($fileProperty)){
            $reflection = new \ReflectionClass(PluginBase::class);
            $fileProperty = $reflection->getProperty("file");
            $fileProperty->setAccessible(true);
        }
        return $fileProperty->getValue($plugin);
    }

    public static function getPluginNamespace(Plugin $plugin) : string{
        return preg_replace("/[a-z_][a-z\d_]*$/i", "", $plugin->getDescription()->getMain());
    }

    public static function getPluginFullName(Plugin $plugin) : string{
        return "{$plugin->getName()}_v{$plugin->getDescription()->getVersion()}";
    }

    public static function getPluginMetadata(Plugin $plugin) : array{
        $description = $plugin->getDescription();
        return [
            "name" => $description->getName(),
            "version" => $description->getVersion(),
            "main" => $description->getMain(),
            "api" => $description->getCompatibleApis(),
            "depend" => $description->getDepend(),
            "description" => $description->getDescription(),
            "authors" => $description->getAuthors(),
            "website" => $description->getWebsite(),
            "creationDate" => time()
        ];
    }
}
