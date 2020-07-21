<?php

declare(strict_types=1);

namespace jkorn\bd;


use jkorn\bd\duels\types\BasicDuelGameType;
use jkorn\bd\gen\BasicDuelsGeneratorInfo;
use jkorn\bd\gen\types\RedDefault;
use jkorn\bd\gen\types\YellowDefault;
use jkorn\bd\queues\BasicQueue;
use jkorn\bd\queues\BasicQueuesManager;
use jkorn\practice\forms\display\properties\FormDisplayStatistic;
use jkorn\practice\level\gen\PracticeGeneratorManager;
use jkorn\practice\player\info\settings\properties\BooleanSettingProperty;
use jkorn\practice\player\info\settings\SettingsInfo;
use jkorn\practice\player\info\stats\properties\IntegerStatProperty;
use jkorn\practice\player\info\stats\StatPropertyInfo;
use jkorn\practice\player\info\stats\StatsInfo;
use jkorn\practice\player\PracticePlayer;
use jkorn\practice\PracticeCore;
use jkorn\practice\scoreboard\display\statistics\ScoreboardStatistic;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class BasicDuelsUtils
{
    const SETTING_PE_ONLY = "duels.basic.pe.only";

    const STATISTIC_DUELS_WINS = "duels.basic.stat.wins";
    const STATISTIC_DUELS_LOSSES = "duels.basic.stat.losses";
    const STATISTIC_DUELS_WL_RATIO = "duels.basic.stat.wl.ratio";

    const STATISTIC_DUEL_TYPE_AWAITING = "duels.basic.stat.type.awaiting";
    const STATISTIC_DUEL_TYPE = "duels.basic.stat.type";

    /**
     * Initializes the generators.
     */
    public static function initGenerators(): void
    {
        PracticeGeneratorManager::registerGenerator(new BasicDuelsGeneratorInfo(RedDefault::class));
        PracticeGeneratorManager::registerGenerator(new BasicDuelsGeneratorInfo(YellowDefault::class));
    }

    /**
     * Registers the form display statistics.
     */
    public static function registerFormDisplayStats(): void
    {
        // Registers the duel type statistic.
        FormDisplayStatistic::registerStatistic(new FormDisplayStatistic(
            self::STATISTIC_DUEL_TYPE,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof BasicDuelGameType)
                {
                    return $data->getDisplayName();
                }

                return "Unknown";
            }
        ));

        // Registers the awaiting players for duel type statistic.
        FormDisplayStatistic::registerStatistic(new FormDisplayStatistic(
            self::STATISTIC_DUEL_TYPE_AWAITING,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof BasicDuelGameType)
                {
                    $manager = PracticeCore::getBaseGameManager()->getGameManager(BasicDuelsManager::NAME);
                    if($manager instanceof BasicDuelsManager)
                    {
                        $queues = $manager->getAwaitingManager();
                        if($queues instanceof BasicQueuesManager)
                        {
                            return $queues->getPlayersAwaitingFor($data);
                        }
                    }
                }

                return 0;
            }
        ));
    }

    /**
     * Unregisters the form display statistics.
     */
    public static function unregisterFormDisplayStats(): void
    {
        FormDisplayStatistic::unregisterStatistic(self::STATISTIC_DUEL_TYPE_AWAITING);
        FormDisplayStatistic::unregisterStatistic(self::STATISTIC_DUEL_TYPE);
    }

    /**
     * Registers the player's settings.
     */
    public static function registerPlayerSettings(): void
    {
        SettingsInfo::registerSetting(
            self::SETTING_PE_ONLY,
            BooleanSettingProperty::class,
            false,
            [
                "enabled" => "Enable PE-Only Basic Duels",
                "disabled" => "Disable PE-Only Basic Duels"
            ]
        );
    }


    /**
     * Unregisters the player settings.
     */
    public static function unregisterPlayerSettings(): void
    {
        SettingsInfo::unregisterSetting(self::SETTING_PE_ONLY);
    }

    /**
     * Initializes the scoreboard statistics.
     */
    public static function registerScoreboardStatistics(): void
    {
        // Registers the wins statistic to the scoreboard.
        ScoreboardStatistic::registerStatistic(new ScoreboardStatistic(
            self::STATISTIC_DUELS_WINS,
            function(Player $player, Server $server)
            {
                if($player instanceof PracticePlayer)
                {
                    $statsInfo = $player->getStatsInfo();
                    $winsStat = $statsInfo->getStatistic(self::STATISTIC_DUELS_WINS);
                    if($winsStat !== null)
                    {
                        return $winsStat->getValue();
                    }
                }
                return 0;
            })
        );

        // Registers the losses statistic to the scoreboard.
        ScoreboardStatistic::registerStatistic(new ScoreboardStatistic(
            self::STATISTIC_DUELS_LOSSES,
            function(Player $player, Server $server)
            {
                if($player instanceof PracticePlayer)
                {
                    $statsInfo = $player->getStatsInfo();
                    $lossesStat = $statsInfo->getStatistic(self::STATISTIC_DUELS_LOSSES);
                    if($lossesStat !== null)
                    {
                        return $lossesStat->getValue();
                    }
                }
                return 0;
            }
        ));

        // Registers the win loss ratio statistic to the scoreboard.
        ScoreboardStatistic::registerStatistic(new ScoreboardStatistic(
            self::STATISTIC_DUELS_WL_RATIO,
            function(Player $player, Server $server)
            {
                if($player instanceof PracticePlayer)
                {
                    $statInfo = $player->getStatsInfo();
                    $winsStat = $statInfo->getStatistic(self::STATISTIC_DUELS_WINS);
                    $lossesStat = $statInfo->getStatistic(self::STATISTIC_DUELS_LOSSES);
                    if($winsStat !== null && $lossesStat !== null)
                    {
                        $lossesValue = $lossesStat->getValue();
                        if($lossesValue === 0)
                        {
                            $lossesValue = 1;
                        }
                        return $winsStat->getValue() / $lossesValue;
                    }
                }
                return 0;
            }
        ));
    }

    /**
     * Unregisters the scoreboard statistics.
     */
    public static function unregisterScoreboardStatistics(): void
    {
        ScoreboardStatistic::unregisterStatistic(self::STATISTIC_DUELS_WINS);
        ScoreboardStatistic::unregisterStatistic(self::STATISTIC_DUELS_LOSSES);
        ScoreboardStatistic::unregisterStatistic(self::STATISTIC_DUELS_WL_RATIO);
    }


    /**
     * Initializes the player's statistics.
     */
    public static function registerPlayerStatistics(): void
    {
        // Duel wins of the player.
        StatsInfo::registerStatistic(new StatPropertyInfo(
            self::STATISTIC_DUELS_WINS,
            IntegerStatProperty::class,
            true
        ));

        // Duel losses of the player.
        StatsInfo::registerStatistic(new StatPropertyInfo(
            self::STATISTIC_DUELS_LOSSES,
            IntegerStatProperty::class,
            true
        ));
    }

    /**
     * Unregisters the player's statistics.
     */
    public static function unregisterPlayerStatistics(): void
    {
        StatsInfo::unregisterStatistic(self::STATISTIC_DUELS_WINS);
        StatsInfo::unregisterStatistic(self::STATISTIC_DUELS_LOSSES);
    }
}