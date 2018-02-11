<?php

namespace presentkim\makepluginplus\command;

use pocketmine\Server;
use pocketmine\command\{
  Command, CommandExecutor, CommandSender
};
use pocketmine\plugin\PluginBase;
use FolderPluginLoader\FolderPluginLoader;
use presentkim\makepluginplus\MakePluginPlus as Plugin;
use presentkim\makepluginplus\util\Translation;

class CommandListener implements CommandExecutor{

    /** @var Plugin */
    protected $owner;

    /** @param Plugin $owner */
    public function __construct(Plugin $owner){
        $this->owner = $owner;
    }

    /**
     * @param CommandSender $sender
     * @param Command       $command
     * @param string        $label
     * @param string[]      $args
     *
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if (!empty($args[0])) {
            /** @var PluginBase[] $plugins */
            $plugins = [];
            $pluginManager = Server::getInstance()->getPluginManager();
            if ($args[0] === '*') {
                foreach ($pluginManager->getPlugins() as $pluginName => $plugin) {
                    if ($plugin->getPluginLoader() instanceof FolderPluginLoader) {
                        $plugins[] = $plugin;
                    }
                }
            } else {
                foreach ($args as $key => $pluginName) {
                    $plugin = $pluginManager->getPlugin($pluginName);
                    if ($plugin === null) {
                        $sender->sendMessage(Plugin::$prefix . Translation::translate('command-makepluginplus@failure-invalid', $pluginName));
                    } elseif (!($plugin->getPluginLoader() instanceof FolderPluginLoader)) {
                        $sender->sendMessage(Plugin::$prefix . Translation::translate('command-makepluginplus@failure-notfolder', $plugin->getName()));
                    } else {
                        $plugins[$pluginName] = $plugin;
                    }
                }
            }
            $sender->sendMessage(Plugin::$prefix . Translation::translate('command-makepluginplus@build-start', count($plugins)));

            $reflection = new \ReflectionClass(PluginBase::class);
            $fileProperty = $reflection->getProperty('file');
            $fileProperty->setAccessible(true);
            if (!file_exists($dataFolder = $this->owner->getDataFolder())) {
                mkdir($dataFolder, 0777, true);
            }
            foreach ($plugins as $pluginName => $plugin) {
                $description = $plugin->getDescription();
                $pharPath = $dataFolder . Translation::translate('phar-name', $pluginName, $pluginVersion = $description->getVersion());
                $filePath = rtrim(str_replace("\\", '/', $fileProperty->getValue($plugin)), '/') . '/';
                $this->owner->buildPhar($plugin, $filePath, $pharPath);
                $sender->sendMessage(Translation::translate('command-makepluginplus@build', $pluginName, $pluginVersion, $pharPath));
            }
            $sender->sendMessage(Plugin::$prefix . Translation::translate('command-makepluginplus@built', count($plugins)));
            return true;
        }
        return false;
    }
}