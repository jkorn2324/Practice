<?php

declare(strict_types=1);

namespace practice;


use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use practice\arenas\ArenaManager;
use practice\entities\FishingHook;
use practice\entities\SplashPotion;
use practice\forms\display\FormDisplayManager;
use practice\forms\display\statistics\FormDisplayStatistic;
use practice\items\ItemManager;
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
    /** @var FormDisplayManager */
    private static $formDisplayManager;
    /** @var ItemManager */
    private static $itemManager;

    /**
     * Called when the plugin enables.
     */
    public function onEnable()
    {
        self::$instance = $this;

        $this->initDataFolder();
        $this->registerEntities();

        // Initializes the statistics.
        ScoreboardStatistic::init();
        // self::$scoreboardDisplayManager = new ScoreboardDisplayManager($this);
        PracticeUtil::initManager(
            self::$scoreboardDisplayManager,
            ScoreboardDisplayManager::class,
            $this
        );

        // TODO: Initialize the display stats.
        // self::$formDisplayManager = new FormDisplayManager($this);
        FormDisplayStatistic::init();
        PracticeUtil::initManager(
            self::$formDisplayManager,
            FormDisplayManager::class,
            $this
        );

        // self::$kitManager = new KitManager($this);
        PracticeUtil::initManager(
            self::$kitManager,
            KitManager::class,
            $this
        );

        // self::$arenaManager = new ArenaManager($this);
        PracticeUtil::initManager(
            self::$arenaManager,
            ArenaManager::class,
            $this
        );

        PracticeUtil::initManager(
            self::$itemManager,
            ItemManager::class,
            $this
        );

        // Initializes the practice listener & task.
        new PracticeListener($this);
        new PracticeTask($this);
    }

    /**
     * Called when the plugin disables.
     */
    public function onDisable() {

        if(self::$kitManager instanceof KitManager)
        {
            self::$kitManager->save();
        }

        if(self::$arenaManager instanceof ArenaManager)
        {
            self::$arenaManager->save();
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
     * @return FormDisplayManager
     *
     * Gets the form display manager.
     */
    public static function getFormDisplayManager(): FormDisplayManager
    {
        return self::$formDisplayManager;
    }

    /**
     * @return ItemManager
     *
     * Gets the item manager.
     */
    public static function getItemManager(): ItemManager
    {
        return self::$itemManager;
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