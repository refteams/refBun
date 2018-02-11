<?php

namespace presentkim\makepluginplus;

use pocketmine\command\PluginCommand;
use pocketmine\plugin\PluginBase;
use presentkim\makepluginplus\command\CommandListener;
use presentkim\makepluginplus\util\Translation;

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
}
