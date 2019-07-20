<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-18
 * Time: 09:02
 */

declare(strict_types=1);

namespace practice;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
//use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use practice\arenas\ArenaHandler;
use practice\commands\advanced\ArenaCommand;
use practice\commands\advanced\KitCommand;
use practice\commands\advanced\MuteCommand;
//use practice\commands\advanced\PartyCommand;
use practice\commands\advanced\RankCommand;
use practice\commands\advanced\ReportCommand;
use practice\commands\advanced\StatsCommand;
use practice\commands\basic\AcceptCommand;
use practice\commands\basic\ClearInventoryCommand;
use practice\commands\basic\DuelCommand;
use practice\commands\basic\ExtinguishCommand;
use practice\commands\basic\FeedCommand;
use practice\commands\basic\FlyCommand;
use practice\commands\basic\FreezeCommand;
use practice\commands\basic\HealCommand;
use practice\commands\basic\KickAllCommand;
use practice\commands\basic\PingCommand;
use practice\commands\basic\PlayerInfoCommand;
use practice\commands\basic\SpawnCommand;
use practice\commands\basic\SpectateCommand;
use practice\commands\basic\TeleportLevelCommand;
use practice\duels\DuelHandler;
use practice\duels\IvsIHandler;
use practice\game\entity\FishingHook;
use practice\game\items\ItemHandler;
use practice\game\SetTimeDayTask;
use practice\kits\KitHandler;
use practice\manager\MysqlManager;
use practice\misc\LoadLevelTask;
use practice\parties\PartyManager;
use practice\player\gameplay\ChatHandler;
use practice\player\gameplay\ReportHandler;
use practice\player\info\IPHandler;
use practice\player\permissions\PermissionsHandler;
use practice\player\permissions\PermissionsToCfgTask;
use practice\player\PlayerHandler;
use practice\ranks\RankHandler;

class PracticeCore extends PluginBase
{
    private static $instance;

    private static $playerHandler;

    private static $chatHandler;

    private static $rankHandler;

    private static $itemHandler;

    private static $kitHandler;

    private static $arenaHandler;
    
    private static $duelHandler;

    private static $ivsiHandler;

    private static $reportHandler;

    /* @var \practice\player\permissions\PermissionsHandler */
    private static $permissionsHandler;

    private static $partyManager;

    private static $ipHandler;

    private static $mysqlManager;

    private $serverMuted;

    public function onEnable() {

        $this->registerEntities();

        date_default_timezone_set("America/Los_Angeles");

        $this->initDataFolder();
        $this->saveDefaultConfig();
        $this->initMessageConfig();
        $this->initMysqlConfig();
        $this->initNameConfig();
        $this->initRankConfig();
        $this->initCommands();

        self::$instance = $this;

        self::$playerHandler = new PlayerHandler($this);
        self::$kitHandler = new KitHandler();
        self::$arenaHandler = new ArenaHandler();

        self::$mysqlManager = new MysqlManager($this->getDataFolder());

        if(!PracticeUtil::isMysqlEnabled())
            self::$playerHandler->updateLeaderboards();

        self::$itemHandler = new ItemHandler();
        self::$rankHandler = new RankHandler();
        self::$chatHandler = new ChatHandler();
        self::$duelHandler = new DuelHandler();
        self::$ivsiHandler = new IvsIHandler();
        self::$reportHandler = new ReportHandler();
        self::$permissionsHandler = new PermissionsHandler($this);
        self::$partyManager = new PartyManager();
        self::$ipHandler = new IPHandler($this);

        $this->serverMuted = false;

        PracticeUtil::reloadPlayers();

        $scheduler = $this->getScheduler();

        $this->getServer()->getPluginManager()->registerEvents(new PracticeListener($this), $this);
        $scheduler->scheduleDelayedTask(new SetTimeDayTask($this), 10);
        $scheduler->scheduleDelayedTask(new PermissionsToCfgTask(), 10);
        $scheduler->scheduleRepeatingTask(new PracticeTask($this), 1);
    }

    public function onLoad() { $this->loadLevels(); }

    private function loadLevels() : void {

        $worlds = PracticeUtil::getLevelsFromFolder($this);

        $size = count($worlds);

        if($size > 0) {
            foreach($worlds as $world)
                PracticeUtil::loadLevel($world);
        }
    }

    private function initDataFolder() : void {

        $dataFolder = $this->getDataFolder();

        if(!is_dir($dataFolder)){
            mkdir($dataFolder);
        }
    }

    public function setServerMuted(bool $mute) : void {
        $this->serverMuted = $mute;
    }

    public function isServerMuted() : bool {
        return $this->serverMuted;
    }

    public static function getInstance() : PracticeCore {
        return self::$instance;
    }

    public static function getChatHandler() : ChatHandler {
        return self::$chatHandler;
    }

    public static function getPlayerHandler() : PlayerHandler {
        return self::$playerHandler;
    }

    public static function getRankHandler() : RankHandler {
        return self::$rankHandler;
    }

    public static function getItemHandler() : ItemHandler {
        return self::$itemHandler;
    }

    public static function getKitHandler() : KitHandler {
        return self::$kitHandler;
    }

    public static function getArenaHandler() : ArenaHandler {
        return self::$arenaHandler;
    }

    public static function getDuelHandler() : DuelHandler {
        return self::$duelHandler;
    }

    public static function get1vs1Handler() : IvsIHandler {
        return self::$ivsiHandler;
    }

    public static function getReportHandler() : ReportHandler {
        return self::$reportHandler;
    }

    public static function getPermissionHandler() : PermissionsHandler {
        return self::$permissionsHandler;
    }

    public static function getPartyManager() : PartyManager {
        return self::$partyManager;
    }

    public static function getIPHandler() : IPHandler {
        return self::$ipHandler;
    }

    public static function getMysqlHandler() : MysqlManager {
        return self::$mysqlManager;
    }

    private function initMysqlConfig() : void {
        $this->saveResource("mysql.yml");
    }

    private function initMessageConfig() : void {
        $this->saveResource("messages.yml");
    }

    private function initNameConfig() : void {
        $this->saveResource("names.yml");
    }

    private function initRankConfig() : void {
        $this->saveResource("ranks.yml");
    }

    public function getMessageConfig() : Config {
        $path = $this->getDataFolder() . "messages.yml";
        $cfg = new Config($path, Config::YAML);
        return $cfg;
    }

    public function getRankConfig() : Config {
        $path = $this->getDataFolder() . "ranks.yml";
        $cfg = new Config($path, Config::YAML);
        return $cfg;
    }

    public function getNameConfig() : Config {
        $path = $this->getDataFolder() . "names.yml";
        $cfg = new Config($path, Config::YAML);
        return $cfg;
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        return parent::onCommand($sender, $command, $label, $args);
    }
    
    private function registerCommand(Command $cmd) : void {
        $this->getServer()->getCommandMap()->register($cmd->getName(), $cmd);
    }

    private function unregisterCommand(string $name) : void {
        $map = $this->getServer()->getCommandMap();
        $cmd = $map->getCommand($name);
        if($cmd !== null) $this->getServer()->getCommandMap()->unregister($cmd);
    }

    private function initCommands() : void {

        $this->registerCommand(new KitCommand());
        $this->registerCommand(new ExtinguishCommand());
        $this->registerCommand(new ClearInventoryCommand());
        $this->registerCommand(new FeedCommand());
        $this->registerCommand(new FlyCommand());
        $this->registerCommand(new FreezeCommand());
        $this->registerCommand(new FreezeCommand(false));
        $this->registerCommand(new MuteCommand());
        $this->registerCommand(new RankCommand());
        $this->registerCommand(new SpawnCommand());
        $this->registerCommand(new ArenaCommand());
        $this->registerCommand(new HealCommand());
        $this->registerCommand(new DuelCommand());
        $this->registerCommand(new AcceptCommand());
        $this->registerCommand(new ReportCommand());
        $this->registerCommand(new SpectateCommand());
        $this->registerCommand(new StatsCommand());
        $this->registerCommand(new PingCommand());
        $this->registerCommand(new TeleportLevelCommand());
        $this->registerCommand(new KickAllCommand());
        $this->registerCommand(new PlayerInfoCommand());
        //TODO REGISTER THE COMMAND WHEN IT'S FINISHED
        //$this->registerCommand(new PartyCommand());

        /*$this->unregisterCommand('ban');
        $this->unregisterCommand('banip');
        $this->unregisterCommand('banlist');*/
    }

    private function registerEntities() : void {
        Entity::registerEntity(FishingHook::class, false, ["FishingHook", "minecraft:fishing_hook"]);
    }
}