<?php

declare(strict_types=1);

namespace practice;


use pocketmine\plugin\PluginBase;
use practice\arenas\ArenaManager;

class PracticeCore extends PluginBase
{
    /** @var PracticeCore */
    private static $instance;

    /** @var ArenaManager */
    private static $arenaManager;

    /**
     * Called when the plugin enables.
     */
    public function onEnable()
    {
        self::$instance = $this;

        $this->initDataFolder();

        self::$arenaManager = new ArenaManager($this);

        new PracticeListener($this);
    }

    /**
     * Called when the plugin disables.
     */
    public function onDisable() {}

    /**
     * Initializes the data folder.
     */
    private function initDataFolder(): void
    {
        if(!is_dir($this->getDataFolder()))
        {
            mkdir($this->getDataFolder());
        }
    }

    /**
     * @return PracticeCore
     *
     * Gets the instance of the main core.
     */
    public static function getInstance()
    {
        return self::$instance;
    }

    /**
     * @return ArenaManager
     *
     * Gets the arena manager.
     */
    public static function getArenaManager(): ArenaManager
    {
        return self::$arenaManager;
    }
}