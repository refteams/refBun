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
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\ScriptPluginLoader;
use pocketmine\Server;
use pocketmine\utils\TextFormat as C;

class BuildCommandExecutor implements CommandExecutor{
    /**
     * @param string[] $args
     *
     * @throws \ReflectionException
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if(empty($args))
            return false;

        $pluginManager = Server::getInstance()->getPluginManager();
        if($args[0] === "*"){
            $args = [];
            foreach($pluginManager->getPlugins() as $pluginName => $plugin){
                $args[] = $plugin->getName();
            }
        }
        $sender->sendMessage(C::AQUA . "[PluginBuild] Start build the " . count($args) . " plugins. (create in " . C::DARK_AQUA . BluginTools::cleanDirName(BluginTools::getInstance()->getDataFolder()) . C::AQUA . ")");

        $successes = [];
        $failures = [];
        foreach($args as $key => $pluginName){
            /** @var PluginBase|null $plugin */
            $plugin = BluginTools::getPlugin($pluginName);
            if($plugin === null){
                $failures[] = $pluginName;
                $sender->sendMessage(C::DARK_GRAY . " - $pluginName is invalid plugin name");
            }elseif($plugin->getPluginLoader() instanceof ScriptPluginLoader){
                $failures[] = $pluginName;
                $sender->sendMessage(C::DARK_GRAY . " - {$plugin->getName()} is script plugin");
            }else{
                $successes[] = $pluginName;
                $this->buildPlugin($plugin);
                $sender->sendMessage(C::DARK_GRAY . " + {$plugin->getName()} has been builded to {$plugin->getName()}_v{$plugin->getDescription()->getVersion()}.phar");
            }
        }
        $sender->sendMessage(C::AQUA . "[PluginBuild] All plugin builds are complete. " . C::GREEN . count($successes) . " successes  " . C::RED . count($failures) . " failures");
        $sender->sendMessage(C::AQUA . " - Results (" . count($args) . "): " . C::GREEN . implode(", ", $successes) . C::RESET . ", " . C::RED . implode(", ", $failures));
        return true;
    }

    /** @throws \ReflectionException */
    public function buildPlugin(PluginBase $plugin) : void{
        $reflection = new \ReflectionClass(PluginBase::class);
        $fileProperty = $reflection->getProperty("file");
        $fileProperty->setAccessible(true);
        $sourcePath = BluginTools::cleanDirName($fileProperty->getValue($plugin));

        $pharPath = BluginTools::getInstance()->getDataFolder() . "{$plugin->getName()}_v{$plugin->getDescription()->getVersion()}.phar";

        $description = $plugin->getDescription();
        $metadata = [
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
        AdvancedBuilder::getInstance()->buildPhar($sourcePath, $pharPath, $metadata);
    }
}
