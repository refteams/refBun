<?php

namespace presentkim\makepluginplus;

use pocketmine\command\PluginCommand;
use pocketmine\plugin\PluginBase;
use presentkim\makepluginplus\command\CommandListener;
use presentkim\makepluginplus\util\{
  Translation, Utils
};

class MakePluginPlus extends PluginBase{

    /** @var self */
    private static $instance = null;

    /** @var string */
    public static $prefix = '';

    /** @return self */
    public static function getInstance() : self{
        return self::$instance;
    }

    /** @var PluginCommand */
    private $command = null;

    public function onLoad() : void{
        if (self::$instance === null) {
            self::$instance = $this;
            Translation::loadFromResource($this->getResource('lang/eng.yml'), true);
        }
    }

    public function onEnable() : void{
        $dataFolder = $this->getDataFolder();
        if (!file_exists($dataFolder)) {
            mkdir($dataFolder, 0777, true);
        }

        $this->reloadConfig();

        $langfilename = $dataFolder . 'lang.yml';
        if (!file_exists($langfilename)) {
            $resource = $this->getResource('lang/eng.yml');
            fwrite($fp = fopen("{$dataFolder}lang.yml", "wb"), $contents = stream_get_contents($resource));
            fclose($fp);
            Translation::loadFromContents($contents);
        } else {
            Translation::load($langfilename);
        }

        self::$prefix = Translation::translate('prefix');
        if ($this->command !== null) {
            $this->getServer()->getCommandMap()->unregister($this->command);
        }
        $this->command = new PluginCommand(Translation::translate('command-makepluginplus'), $this);
        $this->command->setExecutor(new CommandListener($this));
        $this->command->setPermission('makepluginplus.cmd');
        $this->command->setDescription(Translation::translate('command-makepluginplus@description'));
        $this->command->setUsage(Translation::translate('command-makepluginplus@usage'));
        if (is_array($aliases = Translation::getArray('command-makepluginplus@aliases'))) {
            $this->command->setAliases($aliases);
        }
        $this->getServer()->getCommandMap()->register('makepluginplus', $this->command);
    }

    /**
     * @param string $name = ''
     *
     * @return PluginCommand
     */
    public function getCommand(string $name = '') : PluginCommand{
        return $this->command;
    }

    /** @param PluginCommand $command */
    public function setCommand(PluginCommand $command) : void{
        $this->command = $command;
    }

    /**
     * @param PluginBase $plugin
     * @param string     $pharPath
     * @param string     $filePath
     */
    public function buildPhar(PluginBase $plugin, string $filePath, string $pharPath) : void{
        $setting = $this->getConfig()->getAll();
        $description = $plugin->getDescription();
        if (file_exists($pharPath)) {
            \Phar::unlinkArchive($pharPath);
        }
        $phar = new \Phar($pharPath);
        $phar->setSignatureAlgorithm(\Phar::SHA1);
        if (!$setting['skip-metadata']) {
            $phar->setMetadata([
              'name'         => $description->getName(),
              'version'      => $description->getVersion(),
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
            $phar->setStub('<?php echo "PocketMine-MP plugin ' . "{$description->getName()}_v{$description->getVersion()}\nThis file has been generated using MakePluginPlus at " . date("r") . '\n----------------\n";if(extension_loaded("phar")){$phar = new \Phar(__FILE__);foreach($phar->getMetadata() as $key => $value){echo ucfirst($key).": ".(is_array($value) ? implode(", ", $value):$value)."\n";}} __HALT_COMPILER();');
        } else {
            $phar->setStub('<?php __HALT_COMPILER();');
        }

        Utils::removeDirectory($buildFolder = "{$this->getDataFolder()}build/");
        mkdir($buildFolder);
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($filePath)) as $path => $fileInfo) {
            $fileName = $fileInfo->getFilename();
            if ($fileName !== "." || $fileName !== "..") {
                $inPath = substr($path, strlen($filePath));
                $newFilePath = "{$buildFolder}{$inPath}";
                $newFileDir = dirname($newFilePath);
                if (!file_exists($newFileDir)) {
                    mkdir($newFileDir, 0777, true);
                }
                if (substr($path, -4) == '.php') {
                    $contents = \file_get_contents($path);
                    if ($setting['rename-variable']) {
                        $contents = Utils::renameVariable($contents);
                    }
                    if ($setting['remove-whitespace']) {
                        $contents = Utils::removeWhitespace($contents);
                    }
                    file_put_contents($newFilePath, $contents);
                } else {
                    copy($path, $newFilePath);
                }
            }
        }
        $phar->startBuffering();
        $phar->buildFromDirectory($filePath);
        if ($setting['compress'] && \Phar::canCompress(\Phar::GZ)) {
            $phar->compressFiles(\Phar::GZ);
        }
        $phar->stopBuffering();
        Utils::removeDirectory($buildFolder = "{$this->getDataFolder()}build/");
    }
}
