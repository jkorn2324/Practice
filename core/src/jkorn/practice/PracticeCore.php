<?php

declare(strict_types=1);

namespace jkorn\practice;


use jkorn\practice\games\BaseGameManager;
use jkorn\practice\games\player\GamePlayer;
use jkorn\practice\level\gen\PracticeGeneratorManager;
use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use jkorn\practice\arenas\BaseArenaManager;
use jkorn\practice\data\PracticeDataManager;
use jkorn\practice\data\providers\JSONDataProvider;
use jkorn\practice\entities\FishingHook;
use jkorn\practice\entities\SplashPotion;
use jkorn\practice\forms\display\FormDisplayManager;
use jkorn\practice\forms\display\statistics\FormDisplayStatistic;
use jkorn\practice\items\ItemManager;
use jkorn\practice\kits\KitManager;
use jkorn\practice\player\info\settings\SettingsInfo;
use jkorn\practice\scoreboard\display\ScoreboardDisplayManager;
use jkorn\practice\scoreboard\display\statistics\ScoreboardStatistic;

class PracticeCore extends PluginBase
{
    /** @var PracticeCore */
    private static $instance;

    /** @var BaseArenaManager */
    private static $baseArenaManager;
    /** @var KitManager */
    private static $kitManager;
    /** @var ScoreboardDisplayManager */
    private static $scoreboardDisplayManager;
    /** @var FormDisplayManager */
    private static $formDisplayManager;
    /** @var ItemManager */
    private static $itemManager;
    /** @var BaseGameManager */
    private static $baseGameManager;

    /**
     * Called when the plugin enables.
     */
    public function onEnable()
    {
        self::$instance = $this;

        $this->initDataFolder();
        $this->registerEntities();

        // Default test data provider is a JSON data provider.
        PracticeDataManager::setDataProvider(new JSONDataProvider());

        // Initializes the practice generator manager.
        PracticeGeneratorManager::init();
        // Initializes the game player properties.
        GamePlayer::init();
        // The settings information to initialize.
        SettingsInfo::init();

        // Initializes the scoreboard statistics.
        ScoreboardStatistic::init();
        self::$scoreboardDisplayManager = new ScoreboardDisplayManager($this);

        // TODO: Initialize the display stats.
        FormDisplayStatistic::init();
        self::$formDisplayManager = new FormDisplayManager($this);

        self::$kitManager = new KitManager($this);
        self::$baseArenaManager = new BaseArenaManager($this);
        self::$baseGameManager = new BaseGameManager($this);
        self::$itemManager = new ItemManager($this);

        // Reloads the players.
        PracticeUtil::reloadPlayers();

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

        if(self::$baseArenaManager instanceof BaseArenaManager)
        {
            self::$baseArenaManager->save();
        }

        // Saves all of the players information.
        PracticeDataManager::getDataProvider()->saveAllPlayers();
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
     * @return BaseArenaManager
     *
     * Gets the arena manager.
     */
    public static function getBaseArenaManager(): BaseArenaManager
    {
        return self::$baseArenaManager;
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
     * @return BaseGameManager
     *
     * Gets the base game manager.
     */
    public static function getBaseGameManager(): BaseGameManager
    {
        return self::$baseGameManager;
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