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
            $setting = $this->owner->getConfig()->getAll();
            foreach ($plugins as $pluginName => $plugin) {
                $description = $plugin->getDescription();
                $pharPath = $dataFolder . Translation::translate('phar-name', $pluginName, $pluginVersion = $description->getVersion());
                if (file_exists($pharPath)) {
                    \Phar::unlinkArchive($pharPath);
                }
                $phar = new \Phar($pharPath);
                $phar->setSignatureAlgorithm(\Phar::SHA1);
                if (!$setting['skip-metadata']) {
                    $phar->setMetadata([
                      'name'         => $pluginName,
                      'version'      => $pluginVersion,
                      'main'         => $description->getMain(),
                      'api'          => $description->getCompatibleApis(),
                      'depend'       => $description->getDepend(),
                      'description'  => $description->getDescription(),
                      'authors'      => $description->getAuthors(),
                      'website'      => $description->getWebsite(),
                      'creationDate' => time(),
                    ]);
                }
                if ($setting['skip-stub']) {
                    $phar->setStub('<?php echo "PocketMine-MP plugin ' . "{$pluginName}_v{$pluginVersion}\nThis file has been generated using MakePluginPlus at " . date("r") . '\n----------------\n";if(extension_loaded("phar")){$phar = new \Phar(__FILE__);foreach($phar->getMetadata() as $key => $value){echo ucfirst($key).": ".(is_array($value) ? implode(", ", $value):$value)."\n";}} __HALT_COMPILER();');
                } else {
                    $phar->setStub('<?php __HALT_COMPILER();');
                }
                $phar->startBuffering();
                $phar->buildFromDirectory(rtrim(str_replace("\\", '/', $fileProperty->getValue($plugin)), '/') . '/');
                if ($setting['compress'] && \Phar::canCompress(\Phar::GZ)) {
                    $phar->compressFiles(\Phar::GZ);
                }
                $phar->stopBuffering();
                $sender->sendMessage(Translation::translate('command-makepluginplus@build', $pluginName, $pluginVersion, $pharPath));
            }
            $sender->sendMessage(Plugin::$prefix . Translation::translate('command-makepluginplus@built', count($plugins)));
            return true;
        }
        return false;
    }
}