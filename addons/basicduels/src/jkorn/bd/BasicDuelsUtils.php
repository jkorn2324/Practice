<?php

declare(strict_types=1);

namespace jkorn\bd;


use jkorn\bd\duels\Basic1vs1;
use jkorn\bd\duels\IBasicDuel;
use jkorn\bd\duels\types\BasicDuelGameType;
use jkorn\bd\gen\BasicDuelsGeneratorInfo;
use jkorn\bd\gen\types\RedDefault;
use jkorn\bd\gen\types\YellowDefault;
use jkorn\bd\queues\BasicQueue;
use jkorn\bd\queues\BasicQueuesManager;
use jkorn\practice\forms\display\properties\FormDisplayStatistic;
use jkorn\practice\games\duels\AbstractDuel;
use jkorn\practice\kits\IKit;
use jkorn\practice\kits\SavedKit;
use jkorn\practice\level\gen\PracticeGeneratorManager;
use jkorn\practice\player\info\settings\properties\BooleanSettingProperty;
use jkorn\practice\player\info\settings\SettingsInfo;
use jkorn\practice\player\info\stats\properties\IntegerStatProperty;
use jkorn\practice\player\info\stats\StatPropertyInfo;
use jkorn\practice\player\info\stats\StatsInfo;
use jkorn\practice\player\PracticePlayer;
use jkorn\practice\PracticeCore;
use jkorn\practice\scoreboard\display\statistics\ScoreboardStatistic;
use old\practice\duels\groups\QueuedPlayer;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class BasicDuelsUtils
{
    const SETTING_PE_ONLY = "duels.basic.pe.only";

    const STATISTIC_DUELS_WINS = "duels.basic.stat.wins";
    const STATISTIC_DUELS_LOSSES = "duels.basic.stat.losses";
    const STATISTIC_DUELS_WL_RATIO = "duels.basic.stat.wl.ratio";

    const STATISTIC_DUEL_TYPE_AWAITING_KIT = "duels.basic.stat.type.kit.awaiting";
    const STATISTIC_DUEL_TYPE_AWAITING = "duels.basic.stat.type.awaiting";
    const STATISTIC_DUEL_TYPE = "duels.basic.stat.type";
    const STATISTIC_DUEL_TYPE_KIT = "duels.basic.stat.type.kit";

    const STATISTIC_PLAYER_DUEL_GAME_TYPE = "duels.basic.stat.type.player";
    const STATISTIC_PLAYER_DUEL_KIT = "duels.basic.stat.type.player.kit";
    const STATISTIC_DUELS_PLAYER_OPPONENT = "duels.basic.stat.player.opponent";
    const STATISTIC_DUELS_DURATION = "duels.basic.stat.duration";

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

        // Registers the duel type statistic.
        FormDisplayStatistic::registerStatistic(new FormDisplayStatistic(
           self::STATISTIC_DUEL_TYPE_KIT,
           function(Player $player, Server $server, $data)
           {
               if($data instanceof IKit)
               {
                   return $data->getName();
               }
               elseif (is_array($data) && isset($data["kit"]))
               {
                   $kit = $data["kit"];
                   if($kit instanceof IKit)
                   {
                       return $kit->getName();
                   }
                   elseif (is_string($kit))
                   {
                       return $kit;
                   }
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
                            return $queues->getPlayersAwaiting(
                                function(BasicQueue $queue) use($data) {
                                    return $queue->getGameType()->equals($data);
                                });
                        }
                    }
                }

                return 0;
            }
        ));

        // Adds the duel kit type statistic.
        FormDisplayStatistic::registerStatistic(new FormDisplayStatistic(
            self::STATISTIC_DUEL_TYPE_AWAITING_KIT,
            function(Player $player, Server $server, $data)
            {
                if(is_array($data) && isset($data["type"], $data["kit"]))
                {
                    /** @var IKit $kit */
                    $kit = $data["kit"];
                    /** @var BasicDuelGameType $type */
                    $type = $data["type"];

                    $manager = PracticeCore::getBaseGameManager()->getGameManager(BasicDuelsManager::NAME);
                    if($manager instanceof BasicDuelsManager)
                    {
                        $queues = $manager->getAwaitingManager();
                        if($queues instanceof BasicQueuesManager)
                        {
                            return $queues->getPlayersAwaiting(function(BasicQueue $queue) use($kit, $type)
                            {
                                return $kit->equals($queue->getKit()) && $type->equals($queue->getGameType());
                            });
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
        FormDisplayStatistic::unregisterStatistic(self::STATISTIC_DUEL_TYPE_AWAITING_KIT);
    }

    /**
     * Registers the player's settings.
     */
    public static function registerPlayerSettings(): void
    {
        SettingsInfo::registerSetting(
            self::SETTING_PE_ONLY,
            BooleanSettingProperty::class,
            [
                "enabled" => "Enable PE-Only Basic Duels",
                "disabled" => "Disable PE-Only Basic Duels"
            ], false
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

        // Gets the player's opponent.
        ScoreboardStatistic::registerStatistic(new ScoreboardStatistic(
            self::STATISTIC_DUELS_PLAYER_OPPONENT,
            function(Player $player, Server $server)
            {
                if($player instanceof PracticePlayer)
                {
                    $game = $player->getCurrentGame();
                    if($game instanceof Basic1vs1)
                    {
                        return $game->getOpponent($player)->getDisplayName();
                    }
                }

                return "Unknown";
            }
        , false));

        // Registers the statistic.
        ScoreboardStatistic::registerStatistic(new ScoreboardStatistic(
            self::STATISTIC_PLAYER_DUEL_GAME_TYPE,
            function(Player $player, Server $server)
            {
                if($player instanceof PracticePlayer)
                {
                    $awaiting = $player->getAwaitingGameType();
                    $game = $player->getCurrentGame();

                    if
                    (
                        $awaiting !== null
                        && $awaiting instanceof BasicQueuesManager
                        && ($queuesManager = $awaiting->getAwaitingManager()) !== null
                        && $queuesManager instanceof BasicQueuesManager
                    )
                    {
                        $queue = $queuesManager->getAwaiting($player);
                        if($queue !== null)
                        {
                            return $queue->getGameType()->getDisplayName();
                        }
                    }
                    elseif
                    (
                        $game !== null
                        && $game instanceof IBasicDuel
                    )
                    {
                        return $game->getGameType()->getDisplayName();
                    }
                }

                return "Unknown";
            }
        , false));

        // Registers the duration to the duel utils.
        ScoreboardStatistic::registerStatistic(new ScoreboardStatistic(
            self::STATISTIC_DUELS_DURATION,
            function(Player $player, Server $server)
            {
                if($player instanceof PracticePlayer)
                {
                    $game = $player->getCurrentGame();
                    if(
                        $game !== null
                        && $game instanceof AbstractDuel
                        && $game instanceof IBasicDuel
                    )
                    {
                        return $game->getDuration();
                    }
                }

                return "00:00";
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