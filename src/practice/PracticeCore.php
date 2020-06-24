<?php

declare(strict_types=1);

namespace practice;


use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use practice\arenas\ArenaManager;
use practice\entities\FishingHook;
use practice\entities\SplashPotion;
use practice\kits\KitManager;
use practice\scoreboard\display\statistics\ScoreboardStatistic;
use practice\scoreboard\ScoreboardDisplayManager;

class PracticeCore extends PluginBase
{
    /** @var PracticeCore */
    private static $instance;

    /** @var ArenaManager */
    private static $arenaManager;
    /** @var KitManager */
    private static $kitManager;
    /** @var ScoreboardDisplayManager */
    private static $scoreboardDisplayManager;

    /**
     * Called when the plugin enables.
     */
    public function onEnable()
    {
        self::$instance = $this;

        $this->initDataFolder();

        // Initializes the statistics.
        ScoreboardStatistic::init();
        self::$scoreboardDisplayManager = new ScoreboardDisplayManager($this);

        self::$kitManager = new KitManager($this);
        self::$arenaManager = new ArenaManager($this);

        new PracticeListener($this);
    }

    /**
     * Called when the plugin disables.
     */
    public function onDisable() {

        if(self::$kitManager instanceof KitManager)
        {
            self::$kitManager->save();
        }
    }

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

    /**
     * @return KitManager
     *
     * Gets the kit manager.
     */
    public static function getKitManager(): KitManager
    {
        return self::$kitManager;
    }

    /**
     * @return ScoreboardDisplayManager
     *
     * Gets the scoreboard display manager.
     */
    public static function getScoreboardDisplayManager(): ScoreboardDisplayManager
    {
        return self::$scoreboardDisplayManager;
    }

    /**
     * @return string
     *
     * Gets the resources folder.
     */
    public function getResourcesFolder(): string
    {
        return $this->getFile() . "resources/";
    }

    /**
     * Registers the entities to the server.
     */
    private function registerEntities(): void
    {
        Entity::registerEntity(SplashPotion::class, false, ['ThrownPotion', 'minecraft:potion', 'thrownpotion']);
        Entity::registerEntity(FishingHook::class, false, ["FishingHook", "minecraft:fishing_hook"]);
    }

}