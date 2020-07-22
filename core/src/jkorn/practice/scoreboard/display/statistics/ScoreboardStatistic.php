<?php

declare(strict_types=1);

namespace jkorn\practice\scoreboard\display\statistics;


use jkorn\practice\player\info\stats\StatsInfo;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use jkorn\practice\player\PracticePlayer;

class ScoreboardStatistic
{

    /** @var string */
    protected $localizedName;
    /** @var callable */
    protected $callable;

    /** @var mixed */
    protected $value;

    /** @var Server */
    protected $server;

    /** @var bool */
    protected $update;

    public function __construct(string $localizedName, callable $callable, bool $update = true)
    {
        $this->localizedName = $localizedName;
        $this->callable = $callable;

        $this->update = $update;
        $this->server = Server::getInstance();
    }

    /**
     * @return bool
     *
     * Determines whether the statistic should update.
     */
    public function doUpdate(): bool
    {
        return $this->update;
    }

    /**
     * @return string
     *
     * Gets the localized name.
     */
    public function getLocalized(): string
    {
        return $this->localizedName;
    }

    /**
     * @param Player $player - The player input.
     * @return mixed
     *
     * Updates and gets the value of the statistic.
     */
    public function getValue(Player $player)
    {
        $callable = $this->callable;
        return $callable($player, $this->server);
    }

    // -------------------------------------------------------------------------------

    const STATISTIC_KILLS = "stat.kills";
    const STATISTIC_DEATHS = "stat.deaths";
    const STATISTIC_KDR = "stat.kdr";
    const STATISTIC_CPS = "stat.cps";
    const STATISTIC_ONLINE = "stat.online";
    const STATISTIC_PING = "stat.ping";
    const STATISTIC_NAME = "stat.player.name";
    const STATISTIC_OS = "stat.os";
    const STATISTIC_EQUIPPED_KIT = "stat.equipped.kit";
    const STATISTIC_RANK = "stat.rank";

    const STATISTIC_OPPONENT = "opponent";
    const STATISTIC_OPPONENT_CPS = "opponent.cps";
    const STATISTIC_OPPONENT_PING = "opponent.ping";
    const STATISTIC_DUEL_ARENA = "duel.arena";
    const STATISTIC_DURATION = "duration"; // TODO: Sync with Duels Manager.
    const STATISTIC_SPECTATORS = "spectators";

    /** @var ScoreboardStatistic[] */
    private static $statistics = [];

    /**
     * Initializes the specific statistics.
     */
    public static function init(): void
    {
        // Registers the kills statistic.
        self::registerStatistic(new ScoreboardStatistic(
            self::STATISTIC_KILLS,

            function(Player $player, Server $server)
            {
                if($player instanceof PracticePlayer)
                {
                    $statistics = $player->getStatsInfo();
                    if($statistics !== null)
                    {
                        $killsStat = $statistics->getStatistic(StatsInfo::STAT_KILLS);
                        if($killsStat !== null)
                        {
                            return $killsStat->getValue();
                        }
                    }
                }

                return 0;
            })
        );

        // Registers the deaths statistic.
        self::registerStatistic(new ScoreboardStatistic(
            self::STATISTIC_DEATHS,
            function(Player $player, Server $server)
            {
                if($player instanceof PracticePlayer)
                {
                    $statistics = $player->getStatsInfo();
                    if($statistics !== null)
                    {
                        $killsStat = $statistics->getStatistic(StatsInfo::STAT_DEATHS);
                        if($killsStat !== null)
                        {
                            return $killsStat->getValue();
                        }
                    }
                }

                return 0;
            }
        ));

        // Initializes the kdr statistic.
        self::registerStatistic(new ScoreboardStatistic(
            self::STATISTIC_KDR,
            function(Player $player, Server $server)
            {
                if($player instanceof PracticePlayer)
                {
                    $statistics = $player->getStatsInfo();
                    if($statistics !== null)
                    {
                        $kills = $statistics->getStatistic(StatsInfo::STAT_KILLS);
                        $deaths = $statistics->getStatistic(StatsInfo::STAT_DEATHS);
                        if($kills !== null && $deaths !== null)
                        {
                            $deaths = $deaths->getValue();
                            if($deaths === 0)
                            {
                                $deaths = 1;
                            }

                            return (float)$kills->getValue() / (float)$deaths;
                        }
                    }
                }
                return 0;
            }
        ));

        // Registers the cps statistic.
        self::registerStatistic(new ScoreboardStatistic(
            self::STATISTIC_CPS,
            function(Player $player, Server $server)
            {
                if($player instanceof PracticePlayer)
                {
                    return $player->getClicksInfo()->getCps();
                }
                return 0;
            }
        ));

        // Registers the online statistic.
        self::registerStatistic(new ScoreboardStatistic(
            self::STATISTIC_ONLINE,
            function(Player $player, Server $server)
            {
                return count($server->getOnlinePlayers());
            }
        ));

        // Registers the ping statistic.
        self::registerStatistic(new ScoreboardStatistic(
            self::STATISTIC_PING,
            function(Player $player, Server $server)
            {
                return $player->getPing();
            }
        ));

        // Register the display name statistic.
        self::registerStatistic(new ScoreboardStatistic(
            self::STATISTIC_NAME,
            function(Player $player, Server $server)
            {
                return $player->getDisplayName();
            }
        ));

        // Registers the statistic.
        self::registerStatistic(new ScoreboardStatistic(
            self::STATISTIC_OS,
            function(Player $player, Server $server)
            {
                if($player instanceof PracticePlayer)
                {
                    $clientInfo = $player->getClientInfo();
                    if($clientInfo !== null)
                    {
                        return $clientInfo->getDeviceOS(true);
                    }
                }
                return TextFormat::RED . "[Unknown]" . TextFormat::RESET;
            }
        , false));

        // Gets the equipped kit statistic.
        self::registerStatistic(new ScoreboardStatistic(
            self::STATISTIC_EQUIPPED_KIT,
            function(Player $player, Server $server)
            {
                if($player instanceof PracticePlayer)
                {
                    $kit = $player->getEquippedKit();
                    if($kit !== null)
                    {
                        return $kit->getName();
                    }
                    return "None";
                }

                return TextFormat::RED . "[Unknown]";
            }
        , false));

        // Gets the player's rank statistic.
        self::registerStatistic(new ScoreboardStatistic(
            self::STATISTIC_RANK,
            function(Player $player, Server $server)
            {
                // TODO: Do the rank statistic.
                return TextFormat::RED . "[Unknown]";
            }
        ));
    }

    /**
     * @param ScoreboardStatistic $statistic
     * @param bool $override - Determines whether or not we want to override the statistic.
     *
     * Registers the statistic to the list of statistics.
     */
    public static function registerStatistic(ScoreboardStatistic $statistic, bool $override = false): void
    {
        if(isset(self::$statistics[$statistic->getLocalized()]) && !$override)
        {
            return;
        }

        self::$statistics[$statistic->getLocalized()] = $statistic;
    }

    /**
     * @param string $localized
     *
     * Unregisters the statistic.
     */
    public static function unregisterStatistic(string $localized): void
    {
        if(isset(self::$statistics[$localized]))
        {
            unset(self::$statistics[$localized]);
        }
    }

    /**
     * @param Player $player - The input player.
     * @param string $message
     *
     * Converts the message containing the statistics.
     */
    public static function convert(Player $player, string &$message): void
    {
        foreach(self::$statistics as $localized => $statistic)
        {
            $statisticVariable = "{{$localized}}";
            if(strpos($message, $statisticVariable) !== 0)
            {
                $message = str_replace($statisticVariable, $statistic->getValue($player), $message);
            }
        }
    }

    /**
     * @param string $message
     * @return bool
     *
     * Determines if the message contains statistics.
     */
    public static function containsStatistics(string &$message): bool
    {
        foreach (self::$statistics as $localized => $statistic)
        {
            $statisticVariable = "{$localized}";
            if(strpos($message, $statisticVariable) !== 0 && $statistic->doUpdate())
            {
                return true;
            }
        }
        return false;
    }
}