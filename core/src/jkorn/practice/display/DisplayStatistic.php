<?php

declare(strict_types=1);

namespace jkorn\practice\display;


use jkorn\practice\kits\IKit;
use jkorn\practice\player\info\stats\StatsInfo;
use jkorn\practice\player\PracticePlayer;
use pocketmine\Player;
use pocketmine\Server;

/**
 * Class DisplayStatistic
 * @package jkorn\practice\display
 *
 * Handles the display statistics for Scoreboards, Forms, and Messages, etc...
 */
class DisplayStatistic implements DisplayStatisticNames
{

    /** @var DisplayStatistic[] */
    private static $statistics = [];

    /**
     * Initializes the default statistics.
     */
    public static function init(): void
    {
        // Registers the maximum players.
        self::register(new DisplayStatistic(
            self::STATISTIC_MAX_PLAYERS,
            function(Player $player, Server $server, $data)
            {
                return $server->getMaxPlayers();
            }
        , false));

        // Registers the online players.
        self::register(new DisplayStatistic(
            self::STATISTIC_ONLINE_PLAYERS,
            function(Player $player, Server $server, $data)
            {
                return count($server->getOnlinePlayers());
            }
        ));

        // Registers the player ping.
        self::register(new DisplayStatistic(
            self::STATISTIC_PLAYER_PING,
            function(Player $player, Server $server, $data)
            {
                return $player->getPing();
            }
        ));

        // Registers the player's name.
        self::register(new DisplayStatistic(
            self::STATISTIC_PLAYER_NAME,
            function(Player $player, Server $server, $data)
            {
                return $player->getDisplayName();
            }
        , false));

        // Registers the player's operating system.
        self::register(new DisplayStatistic(
            self::STATISTIC_PLAYER_OS,
            function(Player $player, Server $server, $data)
            {
                if ($player instanceof PracticePlayer)
                {
                    $clientInfo = $player->getClientInfo();
                }

                if(isset($clientInfo) && $clientInfo !== null)
                {
                    return $clientInfo->getDeviceOS(true);
                }

                return "Unknown";
            }
        , false));

        // Registers the player's kdr, the kills & deaths display statistics are already registered.
        self::register(new DisplayStatistic(
            self::STATISTIC_TOTAL_PLAYER_KDR,
            function(Player $player, Server $server, $data)
            {
                if($player instanceof PracticePlayer)
                {
                    $statistics = $player->getStatsInfo();

                    $kills = $statistics->getStatistic(self::STATISTIC_TOTAL_PLAYER_KILLS);
                    $deaths = $statistics->getStatistic(self::STATISTIC_TOTAL_PLAYER_DEATHS);

                    if($kills !== null && $deaths !== null)
                    {
                        $deathsValue = $deaths->getValue();
                        if($deathsValue === 0)
                        {
                            $deathsValue = 1;
                        }
                        return round(floatval($kills->getValue() / $deathsValue), 2);
                    }
                }

                return 0;
            }
        ));

        // Gets the player cps.
        self::register(new DisplayStatistic(
            self::STATISTIC_PLAYER_CPS,
            function(Player $player, Server $server, $data)
            {
                if($player instanceof PracticePlayer)
                {
                    $clicksInfo = $player->getClicksInfo();
                    return $clicksInfo->getCps();
                }

                return 0;
            }
        ));

        // Gets the name of the equipped kit.
        self::register(new DisplayStatistic(
            self::STATISTIC_PLAYER_EQUIPPED_KIT,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof IKit)
                {
                    return $data->getName();
                }
                elseif($player instanceof PracticePlayer)
                {
                    $equippedKit = $player->getEquippedKit();
                    if($equippedKit !== null)
                    {
                        return $equippedKit->getName();
                    }
                }

                return "Unknown";
            }
        , false));

        // Server's ticks per second.
        self::register(new DisplayStatistic(
            self::STATISTIC_SERVER_CURRENT_TPS,
            function(Player $player, Server $server, $data)
            {
                return round($server->getTicksPerSecond(), 2);
            }
        ));

        // Server's average ticks per second.
        self::register(new DisplayStatistic(
            self::STATISTIC_SERVER_AVERAGE_TPS,
            function(Player $player, Server $server, $data)
            {
                return round($server->getTicksPerSecondAverage(), 2);
            }
        ));

        // Server's current load.
        self::register(new DisplayStatistic(
            self::STATISTIC_SERVER_CURRENT_LOAD,
            function(Player $player, Server $server, $data)
            {
                return round($server->getTickUsage(), 2);
            }
        ));

        self::register(new DisplayStatistic(
            self::STATISTIC_SERVER_AVERAGE_LOAD,
            function(Player $player, Server $server, $data)
            {
                return round($server->getTickUsageAverage(), 2);
            }
        ));
    }

    /**
     * @param DisplayStatistic $statistic
     * @param bool $override
     *
     * Registers the display statistic to the statistics list.
     */
    public static function register(DisplayStatistic $statistic, bool $override = false): void
    {
        if(!$override && isset(self::$statistics[$statistic->getLocalizedName()]))
        {
            return;
        }

        self::$statistics[$statistic->getLocalizedName()] = $statistic;
    }

    /**
     * @param string $statistic
     *
     * Unregisters the statistic based on its localized name.
     */
    public static function unregisterStatistic(string $statistic): void
    {
        if(isset(self::$statistics[$statistic]))
        {
            unset(self::$statistics[$statistic]);
        }
    }

    /**
     * @param string $message
     * @param Player $player
     * @param mixed $args
     *
     * Convert the message based on parameters.
     */
    public static function convert(string &$message, Player $player, $args): void
    {
        foreach (self::$statistics as $localized => $statistic) {
            $statisticVariable = "{{$localized}}";
            if (strpos($message, $statisticVariable) !== false) {
                $message = str_replace($statisticVariable, $statistic->getValue($player, $args), $message);
            }
        }
    }

    /**
     * @param string $text
     * @param bool $checkForUpdate - Determines whether or not we want to check if the statistics update.
     * @return bool
     *
     * Determines if the text contains any statistics.
     */
    public static function containsStatistics(string &$text, bool $checkForUpdate = false): bool
    {
        foreach (self::$statistics as $localized => $statistic)
        {
            $statisticVariable = "{$localized}";

            if(strpos($text, $statisticVariable) !== false)
            {
                if(!$checkForUpdate)
                {
                    return true;
                }

                if($statistic->doUpdateForScoreboards())
                {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param string $text - The input text.
     * @return string - The output text without the statistics.
     *
     * Clears the statistics from the text.
     */
    public static function clearStatistics(string $text): string
    {
        foreach(self::$statistics as $localized => $statistic)
        {
            $statisticVariable = "{$localized}";

            if(($position = strpos($text, $statisticVariable)) !== false)
            {
                $text = str_replace($statisticVariable, "", $text);
            }
        }
        return $text;
    }


    // ----------------------------- The display statistic instance -------------------

    /** @var string */
    private $localizedName;

    /** @var callable */
    private $callable;

    /** @var Server */
    private $server;

    /** @var bool */
    private $updateForScoreboards;

    public function __construct(string $localizedName, callable $callable, bool $updateForScoreboards = true)
    {
        $this->localizedName = $localizedName;
        $this->callable = $callable;
        $this->updateForScoreboards = $updateForScoreboards;

        $this->server = Server::getInstance();
    }

    /**
     * @return bool
     *
     * Determines whether or not the statistic updates for scoreboards.
     */
    public function doUpdateForScoreboards(): bool
    {
        return $this->updateForScoreboards;
    }

    /**
     * @return string
     *
     * Gets the localized name of the display statistic.
     */
    public function getLocalizedName(): string
    {
        return $this->localizedName;
    }

    /**
     * @param Player $player
     * @param mixed|null $data
     *
     * @return mixed
     *
     * Gets the value of the display statistic.
     */
    public function getValue(Player $player, $data = null)
    {
        return ($this->callable)($player, $this->server, $data);
    }
}