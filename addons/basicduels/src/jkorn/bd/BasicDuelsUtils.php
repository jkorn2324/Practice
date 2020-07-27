<?php

declare(strict_types=1);

namespace jkorn\bd;


use jkorn\bd\duels\IBasicDuel;
use jkorn\bd\duels\types\BasicDuelGameType;
use jkorn\bd\gen\BasicDuelsGeneratorInfo;
use jkorn\bd\gen\types\RedDefault;
use jkorn\bd\gen\types\YellowDefault;
use jkorn\bd\queues\BasicQueue;
use jkorn\bd\queues\BasicQueuesManager;
use jkorn\practice\display\DisplayStatistic;
use jkorn\practice\games\duels\AbstractDuel;
use jkorn\practice\games\duels\types\Duel1vs1;
use jkorn\practice\kits\IKit;
use jkorn\practice\level\gen\PracticeGeneratorManager;
use jkorn\practice\player\info\settings\properties\BooleanSettingProperty;
use jkorn\practice\player\info\settings\SettingsInfo;
use jkorn\practice\player\info\stats\properties\IntegerStatProperty;
use jkorn\practice\player\info\stats\StatPropertyInfo;
use jkorn\practice\player\info\stats\StatsInfo;
use jkorn\practice\player\PracticePlayer;
use jkorn\practice\PracticeCore;
use pocketmine\Player;
use pocketmine\Server;

class BasicDuelsUtils
{
    const SETTING_PE_ONLY = "duels.basic.pe.only";

    // Stats that are registered in the statsInfo class.
    const STATISTIC_DUELS_PLAYER_WINS = "duels.basic.stat.wins";
    const STATISTIC_DUELS_PLAYER_LOSSES = "duels.basic.stat.losses";

    const STATISTIC_DUELS_PLAYER_WL_RATIO = "duels.basic.stat.wl.ratio";

    const STATISTIC_DUEL_TYPE_AWAITING_PLAYERS = "duels.basic.stat.type.awaiting.players";
    const STATISTIC_DUEL_TYPE_AWAITING_KIT_PLAYERS = "duels.basic.stat.type.kit.awaiting.players";

    const STATISTIC_DUEL_GAME_TYPE = "duels.basic.stat.type";
    const STATISTIC_DUEL_KIT = "duels.basic.stat.kit";

    const STATISTIC_PLAYER_DUEL_GAME_TYPE = "duels.basic.stat.type.player";
    const STATISTIC_PLAYER_DUEL_KIT = "duels.basic.stat.kit.player";

    const STATISTIC_DUELS_PLAYER_OPPONENT_NAME = "duels.basic.stat.player.opponent.name";
    // TODO: Add these
    const STATISTIC_DUELS_PLAYER_OPPONENT_PING = "duels.basic.stat.player.opponent.ping";
    const STATISTIC_DUELS_PLAYER_OPPONENT_CPS = "duels.basic.stat.player.opponent.cps";

    const STATISTIC_DUELS_PLAYER_TEAM_COLOR = "duels.basic.stat.player.team.color";
    const STATISTIC_DUELS_PLAYER_OPPOSITE_TEAM_COLOR = "duels.basic.stat.player.opposite.team.color";

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
     * Registers the display statistics to the display statistic manager.
     */
    public static function registerDisplayStats(): void
    {
        // Registers the win/loss ratio.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_DUELS_PLAYER_WL_RATIO,
            function (Player $player, Server $server, $data) {
                if ($player instanceof PracticePlayer) {
                    $statInfo = $player->getStatsInfo();

                    $winsStat = $statInfo->getStatistic(self::STATISTIC_DUELS_PLAYER_WINS);
                    $lossesStat = $statInfo->getStatistic(self::STATISTIC_DUELS_PLAYER_LOSSES);

                    if ($winsStat !== null && $lossesStat !== null) {
                        $lossesValue = $lossesStat->getValue();
                        if ($lossesValue === 0) {
                            $lossesValue = 1;
                        }
                        return $winsStat->getValue() / $lossesValue;
                    }
                }
                return 0;
            }
        ));

        // Registers the awaiting players based on the given duel type & the kit.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_DUEL_TYPE_AWAITING_KIT_PLAYERS,
            function (Player $player, Server $server, $data) {
                if (is_array($data) && isset($data["type"], $data["kit"])) {
                    /** @var IKit $kit */
                    $kit = $data["kit"];
                    /** @var BasicDuelGameType $type */
                    $type = $data["type"];
                    $manager = PracticeCore::getBaseGameManager()->getGameManager(BasicDuelsManager::NAME);

                    if ($manager instanceof BasicDuelsManager) {
                        $queues = $manager->getAwaitingManager();
                        if ($queues instanceof BasicQueuesManager) {
                            return $queues->getPlayersAwaiting(function (BasicQueue $queue) use ($kit, $type) {
                                return $kit->equals($queue->getKit()) && $type->equals($queue->getGameType());
                            });
                        }
                    }
                }

                return 0;
            }
        ));

        // Registers the awaiting players based on the given duel type.
        DisplayStatistic::register(new DisplayStatistic(
                self::STATISTIC_DUEL_TYPE_AWAITING_PLAYERS,
                function (Player $player, Server $server, $data) {
                    if ($data instanceof BasicDuelGameType) {
                        $manager = PracticeCore::getBaseGameManager()->getGameManager(BasicDuelsManager::NAME);
                        if ($manager instanceof BasicDuelsManager) {
                            $queues = $manager->getAwaitingManager();
                            if ($queues instanceof BasicQueuesManager) {
                                return $queues->getPlayersAwaiting(
                                    function (BasicQueue $queue) use ($data) {
                                        return $queue->getGameType()->equals($data);
                                    });
                            }
                        }
                    }
                    return 0;
                })
        );

        // Registers the duel game type statistic (1vs1, 2vs2, 3vs3, etc...).
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_DUEL_GAME_TYPE,
            function (Player $player, Server $server, $data) {
                if ($data instanceof BasicDuelGameType) {
                    return $data->getDisplayName();
                } elseif (is_array($data) && isset($data["type"])) {
                    $type = $data["type"];
                    if ($type instanceof BasicDuelGameType) {
                        return $type->getDisplayName();
                    } elseif (is_string($type)) {
                        return $type;
                    }
                }

                return "Unknown";
            }
            , false));

        // Registers the duel kit statistic.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_DUEL_KIT,
            function (Player $player, Server $server, $data) {
                if ($data instanceof IKit) {
                    return $data->getName();
                } elseif (is_array($data) && isset($data["kit"])) {
                    $kit = $data["kit"];
                    if ($kit instanceof IKit) {
                        return $kit->getName();
                    } elseif (is_string($kit)) {
                        return $kit;
                    }
                }

                return "Unknown";
            }
            , false));

        // Registers the player's current duel game type.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_PLAYER_DUEL_GAME_TYPE,
            function (Player $player, Server $server, $data) {
                if ($data instanceof BasicDuelGameType) {
                    return $data->getDisplayName();
                } elseif ($data instanceof IBasicDuel) {
                    return $data->getGameType()->getDisplayName();
                } elseif ($player instanceof PracticePlayer) {
                    $awaiting = $player->getAwaitingGameType();
                    $game = $player->getCurrentGame();

                    if
                    (
                        $awaiting instanceof BasicDuelsManager
                        && ($queuesManager = $awaiting->getAwaitingManager()) !== null
                        && $queuesManager instanceof BasicQueuesManager
                    ) {
                        $queue = $queuesManager->getAwaiting($player);
                        if ($queue !== null) {
                            return $queue->getGameType()->getDisplayName();
                        }
                    } elseif ($game instanceof IBasicDuel) {
                        return $game->getGameType()->getDisplayName();
                    }
                }

                return "Unknown";
            }
            , false));

        // Gets the player's duel kit.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_PLAYER_DUEL_KIT,
            function (Player $player, Server $server, $data) {
                if ($data instanceof IKit) {
                    return $data->getName();
                } elseif ($data instanceof AbstractDuel) {
                    return $data->getKit()->getName();
                } elseif ($player instanceof PracticePlayer) {
                    $awaiting = $player->getAwaitingGameType();
                    $game = $player->getCurrentGame();

                    if
                    (
                        $awaiting instanceof BasicDuelsManager
                        && ($queuesManager = $awaiting->getAwaitingManager()) !== null
                        && $queuesManager instanceof BasicQueuesManager
                    ) {
                        $queue = $queuesManager->getAwaiting($player);
                        if
                        (
                            $queue !== null
                            && ($kit = $queue->getKit()) !== null
                            && $kit instanceof IKit
                        ) {
                            return $kit->getName();
                        }
                    } elseif ($game instanceof AbstractDuel) {
                        return $game->getKit()->getName();
                    }
                }

                return "Unknown";
            }
            , false));

        // Registers the opponent name to the statistics.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_DUELS_PLAYER_OPPONENT_NAME,
            function (Player $player, Server $server, $data) {
                if ($data instanceof PracticePlayer) {
                    return $data->getDisplayName();
                } elseif ($data instanceof Duel1vs1) {
                    $opponent = $data->getOpponent($player);
                    if ($opponent !== null) {
                        return $opponent->getDisplayName();
                    }
                } elseif ($player instanceof PracticePlayer) {
                    $game = $player->getCurrentGame();
                    if ($game instanceof Duel1vs1) {
                        $opponent = $game->getOpponent($player);
                        if ($opponent !== null) {
                            return $opponent->getDisplayName();
                        }
                    }
                }

                return "Unknown";
            }
            , false));

        // Gets the opponent's cps.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_DUELS_PLAYER_OPPONENT_CPS,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof PracticePlayer)
                {
                    return $data->getClicksInfo()->getCps();
                }
                elseif ($data instanceof Duel1vs1)
                {
                    $opponent = $data->getOpponent($player);
                    if($opponent !== null && $opponent->isOnline(true))
                    {
                        return $opponent->getPlayer()->getClicksInfo()->getCps();
                    }
                }
                elseif ($player instanceof PracticePlayer)
                {
                    $game = $player->getCurrentGame();
                    if($game instanceof Duel1vs1)
                    {
                        $opponent = $game->getOpponent($player);
                        if($opponent !== null && $opponent->isOnline(true))
                        {
                            return $opponent->getPlayer()->getClicksInfo()->getCps();
                        }
                    }
                }
                return 0;
            }
        ));

        // Registers the opponent's ping statistic.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_DUELS_PLAYER_OPPONENT_PING,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof PracticePlayer)
                {
                    return $data->getPing();
                }
                elseif ($data instanceof Duel1vs1)
                {
                    $opponent = $data->getOpponent($player);
                    if($opponent !== null && $opponent->isOnline(true))
                    {
                        return $opponent->getPlayer()->getPing();
                    }
                }
                elseif ($player instanceof PracticePlayer)
                {
                    $game = $player->getCurrentGame();
                    if($game instanceof Duel1vs1)
                    {
                        $opponent = $game->getOpponent($player);
                        if($opponent !== null && $opponent->isOnline(true))
                        {
                            return $opponent->getPlayer()->getPing();
                        }
                    }
                }
                return 0;
            }
        ));

        // Registers the duration to the display.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_DUELS_DURATION,
            function (Player $player, Server $server, $data) {
                if ($data instanceof IBasicDuel && $data instanceof AbstractDuel) {
                    return $data->getDuration();
                } elseif ($player instanceof PracticePlayer) {
                    $game = $player->getCurrentGame();
                    if ($game instanceof AbstractDuel) {
                        return $game->getDuration();
                    }
                }

                return "00:00";
            }
        ));
    }

    /**
     * The player unregisters the display statistics.
     */
    public static function unregisterDisplayStats(): void
    {
        DisplayStatistic::unregisterStatistic(self::STATISTIC_DUELS_PLAYER_WL_RATIO);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_DUEL_TYPE_AWAITING_PLAYERS);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_DUEL_TYPE_AWAITING_KIT_PLAYERS);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_DUEL_GAME_TYPE);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_DUEL_KIT);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_PLAYER_DUEL_GAME_TYPE);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_PLAYER_DUEL_KIT);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_DUELS_PLAYER_OPPONENT_NAME);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_DUELS_DURATION);
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
     * Initializes the player's statistics.
     */
    public static function registerPlayerStatistics(): void
    {
        // Duel wins of the player.
        StatsInfo::registerStatistic(new StatPropertyInfo(
            self::STATISTIC_DUELS_PLAYER_WINS,
            IntegerStatProperty::class,
            true
        ));

        // Duel losses of the player.
        StatsInfo::registerStatistic(new StatPropertyInfo(
            self::STATISTIC_DUELS_PLAYER_LOSSES,
            IntegerStatProperty::class,
            true
        ));
    }

    /**
     * Unregisters the player's statistics.
     */
    public static function unregisterPlayerStatistics(): void
    {
        StatsInfo::unregisterStatistic(self::STATISTIC_DUELS_PLAYER_WINS);
        StatsInfo::unregisterStatistic(self::STATISTIC_DUELS_PLAYER_LOSSES);
    }
}