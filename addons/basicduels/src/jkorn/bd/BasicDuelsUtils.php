<?php

declare(strict_types=1);

namespace jkorn\bd;


use jkorn\bd\duels\IBasicDuel;
use jkorn\bd\duels\types\BasicDuelGameInfo;
use jkorn\bd\gen\BasicDuelsGeneratorInfo;
use jkorn\bd\gen\types\RedDefault;
use jkorn\bd\gen\types\YellowDefault;
use jkorn\bd\queues\BasicQueue;
use jkorn\bd\queues\BasicQueuesManager;
use jkorn\practice\display\DisplayStatistic;
use jkorn\practice\games\duels\AbstractDuel;
use jkorn\practice\games\duels\DuelPlayer;
use jkorn\practice\games\duels\teams\DuelTeam;
use jkorn\practice\games\duels\teams\DuelTeamPlayer;
use jkorn\practice\games\duels\types\Duel1vs1;
use jkorn\practice\games\duels\types\TeamDuel;
use jkorn\practice\games\player\GamePlayer;
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

class BasicDuelsUtils implements BasicDuelsStatistics
{
    // The PE Only Setting
    const SETTING_PE_ONLY = "duels.basic.pe.only";

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
                    /** @var BasicDuelGameInfo $type */
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
                    if ($data instanceof BasicDuelGameInfo) {
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
                if ($data instanceof BasicDuelGameInfo) {
                    return $data->getDisplayName();
                } elseif (is_array($data) && isset($data["type"])) {
                    $type = $data["type"];
                    if ($type instanceof BasicDuelGameInfo) {
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
                if ($data instanceof BasicDuelGameInfo) {
                    return $data->getDisplayName();
                } elseif ($data instanceof IBasicDuel) {
                    return $data->getGameType()->getDisplayName();
                } elseif ($player instanceof PracticePlayer) {

                    $awaiting = $player->getAwaitingManager();
                    $game = $player->getCurrentGame();

                    if ($awaiting instanceof BasicQueuesManager) {
                        $queue = $awaiting->getAwaiting($player);
                        if ($queue !== null) {
                            return $queue->getGameType()->getDisplayName();
                        }
                    } elseif ($game instanceof IBasicDuel) {
                        return $game->getGameType()->getDisplayName();
                    }
                }

                return "Unknown";
            })
        );

        // Gets the player's duel kit.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_PLAYER_DUEL_KIT,
            function (Player $player, Server $server, $data) {
                if ($data instanceof IKit) {
                    return $data->getName();
                } elseif ($data instanceof AbstractDuel) {
                    return $data->getKit()->getName();
                } elseif ($player instanceof PracticePlayer) {
                    $awaiting = $player->getAwaitingManager();
                    $game = $player->getCurrentGame();

                    if ($awaiting instanceof BasicQueuesManager) {
                        $queue = $awaiting->getAwaiting($player);
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
            })
        );

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

        // Registers the duel countdown seconds statistic.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_DUELS_COUNTDOWN_SECONDS,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof AbstractDuel)
                {
                    return $data->getCountdownSeconds();
                }
                elseif ($player instanceof PracticePlayer)
                {
                    $game = $player->getCurrentGame();
                    if($game instanceof AbstractDuel)
                    {
                        return $game->getCountdownSeconds();
                    }
                }
                return 5;
            }
        ));

        // The gets the winner name based on the data, data should be a duel or an array.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_DUELS_1VS1_WINNER_NAME,
            function(Player $player, Server $server, $data)
            {
                if(is_array($data) && isset($data["winner"]))
                {
                    $winner = $data["winner"];

                    if($winner instanceof DuelPlayer)
                    {
                        return $winner->getDisplayName();
                    }
                    else
                    {
                        return "None";
                    }
                }
                elseif ($data instanceof IBasicDuel)
                {
                    $results = $data->getResults();
                    if(isset($results["winner"]))
                    {
                        $winner = $results["winner"];
                        if($winner instanceof DuelPlayer)
                        {
                            return $winner->getDisplayName();
                        }
                    }
                    return "None";
                }

                return "Unknown";
            }
        ));

        // Gets the loser's name. The data should be either an array or a basic duel.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_DUELS_1VS1_LOSER_NAME,
            function(Player $player, Server $server, $data)
            {
                if(is_array($data) && isset($data["loser"]))
                {
                    $loser = $data["loser"];
                    if($loser instanceof DuelPlayer)
                    {
                        return $loser->getDisplayName();
                    }
                    else
                    {
                        return "None";
                    }
                }
                elseif ($data instanceof IBasicDuel)
                {
                    $results = $data->getResults();
                    if(isset($results["loser"]))
                    {
                        $loser = $results["loser"];
                        if($loser instanceof DuelPlayer)
                        {
                            return $loser->getDisplayName();
                        }
                    }
                    return "None";
                }

                return "Unknown";
            }
        ));


        // Registers the duels winning team color.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_DUELS_TEAMS_WINNING_TEAM_COLOR,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof IBasicDuel)
                {
                    $results = $data->getResults();
                    if(isset($results["winner"]))
                    {
                        $winner = $results["winner"];
                        if($winner instanceof DuelTeam)
                        {
                            return $winner->getColor()->getColorName();
                        }
                    }

                    return "None";
                }

                return "Unknown";
            }
        ));

        // Registers the losing team color.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_DUELS_TEAMS_LOSING_TEAM_COLOR,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof IBasicDuel)
                {
                    $results = $data->getResults();
                    if(isset($results["loser"]))
                    {
                        $loser = $results["loser"];
                        if($loser instanceof DuelTeam)
                        {
                            return $loser->getColor()->getColorName();
                        }
                    }
                    return "None";
                }

                return "Unknown";
            }
        ));

        // Registers the team color to the displaystatistic list.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_DUELS_PLAYER_TEAM_COLOR,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof TeamDuel)
                {
                    $team = $data->getTeam($player);
                    if($team !== null)
                    {
                        return $team->getColor()->getColorName();
                    }
                }
                elseif ($player instanceof PracticePlayer)
                {
                    $game = $player->getCurrentGame();
                    if($game instanceof TeamDuel)
                    {
                        $team = $game->getTeam($player);
                        if($team !== null)
                        {
                            return $team->getColor()->getColorName();
                        }
                    }
                }

                return "Unknown";
            }
        ));

        // Registers the opposite player's team.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_DUELS_PLAYER_OPPOSITE_TEAM_COLOR,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof TeamDuel)
                {
                    $team = $data->getOppositeTeam($player);
                    if($team !== null)
                    {
                        return $team->getColor()->getColorName();
                    }
                    return "None";
                }
                elseif ($player instanceof PracticePlayer)
                {
                    $game = $player->getCurrentGame();
                    if($game instanceof TeamDuel)
                    {
                        $team = $game->getOppositeTeam($player);
                        if($team !== null)
                        {
                            return $team->getColor()->getColorName();
                        }
                        return "None";
                    }
                }

                return "Unknown";
            }
        ));

        // Gets the number of players eliminated.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_DUELS_PLAYER_TEAM_ELIMINATED,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof TeamDuel)
                {
                    $team = $data->getTeam($player);
                    if($team !== null)
                    {
                        return $team->getEliminated();
                    }
                }
                elseif ($player instanceof PracticePlayer)
                {
                    $game = $player->getCurrentGame();
                    if($game instanceof TeamDuel)
                    {
                        $team = $game->getTeam($player);
                        if($team !== null)
                        {
                            return $team->getEliminated();
                        }
                    }
                }

                return 0;
            }
        ));

        // Registers the player's team left.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_DUELS_PLAYER_TEAM_PLAYERS_LEFT,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof TeamDuel)
                {
                    $team = $data->getTeam($player);
                    if($team !== null)
                    {
                        return $team->getPlayersLeft();
                    }
                }
                elseif ($player instanceof PracticePlayer)
                {
                    $game = $player->getCurrentGame();
                    if($game instanceof TeamDuel)
                    {
                        $team = $game->getTeam($player);
                        if($team !== null)
                        {
                            return $team->getPlayersLeft();
                        }
                    }
                }

                return 0;
            }
        ));

        // Gets the player's team size.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_DUELS_PLAYER_TEAM_SIZE,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof TeamDuel)
                {
                    $team = $data->getTeam($player);
                    if($team !== null)
                    {
                        return $team->getTeamSize();
                    }
                }
                elseif ($player instanceof PracticePlayer)
                {
                    $game = $player->getCurrentGame();
                    if($game instanceof TeamDuel)
                    {
                        $team = $game->getTeam($player);
                        if($team !== null)
                        {
                            return $team->getTeamSize();
                        }
                    }
                }

                return 0;
            }
        ));

        // Gets the number of players eliminated of the opposite team.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_DUELS_PLAYER_OPPOSITE_TEAM_ELIMINATED,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof TeamDuel)
                {
                    $team = $data->getOppositeTeam($player);
                    if($team !== null)
                    {
                        return $team->getEliminated();
                    }
                }
                elseif ($player instanceof PracticePlayer)
                {
                    $game = $player->getCurrentGame();
                    if($game instanceof TeamDuel)
                    {
                        $team = $game->getOppositeTeam($player);
                        if($team !== null)
                        {
                            return $team->getEliminated();
                        }
                    }
                }

                return 0;
            }
        ));

        // Registers the player's team left.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_DUELS_PLAYER_OPPOSITE_TEAM_PLAYERS_LEFT,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof TeamDuel)
                {
                    $team = $data->getOppositeTeam($player);
                    if($team !== null)
                    {
                        return $team->getPlayersLeft();
                    }
                }
                elseif ($player instanceof PracticePlayer)
                {
                    $game = $player->getCurrentGame();
                    if($game instanceof TeamDuel)
                    {
                        $team = $game->getOppositeTeam($player);
                        if($team !== null)
                        {
                            return $team->getPlayersLeft();
                        }
                    }
                }

                return 0;
            }
        ));

        // The player's opposite team size.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_DUELS_PLAYER_OPPOSITE_TEAM_SIZE,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof TeamDuel)
                {
                    $team = $data->getOppositeTeam($player);
                    if($team !== null)
                    {
                        return $team->getTeamSize();
                    }
                }
                elseif ($player instanceof PracticePlayer)
                {
                    $game = $player->getCurrentGame();
                    if($game instanceof TeamDuel)
                    {
                        $team = $game->getOppositeTeam($player);
                        if($team !== null)
                        {
                            return $team->getTeamSize();
                        }
                    }
                }
                return 0;
            }
        ));

        // Registers the player that was eliminated, data consists of the player object.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_DUELS_TEAM_PLAYER_ELIMINATED,
            function(Player $player, Server $server, $data)
            {
                if(is_string($data))
                {
                    return $data;
                }
                elseif($data instanceof PracticePlayer)
                {
                    return $data->getDisplayName();
                }
                elseif ($data instanceof DuelTeamPlayer)
                {
                    return $data->getDisplayName();
                }

                return "None";
            }
        ));

        // Gets the spectator's player name.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_SPECTATOR_PLAYER_NAME,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof Player)
                {
                    return $data->getDisplayName();
                }
                elseif ($data instanceof GamePlayer)
                {
                    return $data->getDisplayName();
                }

                return $player->getDisplayName();
            }
        ));

        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_SPECTATORS_COUNT,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof IBasicDuel)
                {
                    return $data->getSpectatorCount();
                }
                elseif ($player instanceof PracticePlayer)
                {
                    $game = $player->getSpectatingGame();
                    if($game !== null)
                    {
                        return $game->getSpectatorCount();
                    }
                }

                return 0;
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
        DisplayStatistic::unregisterStatistic(self::STATISTIC_DUELS_PLAYER_OPPONENT_CPS);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_DUELS_PLAYER_OPPONENT_PING);

        DisplayStatistic::unregisterStatistic(self::STATISTIC_DUELS_DURATION);

        DisplayStatistic::unregisterStatistic(self::STATISTIC_DUELS_TEAMS_WINNING_TEAM_COLOR);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_DUELS_TEAMS_LOSING_TEAM_COLOR);

        DisplayStatistic::unregisterStatistic(self::STATISTIC_DUELS_PLAYER_TEAM_COLOR);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_DUELS_PLAYER_TEAM_PLAYERS_LEFT);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_DUELS_PLAYER_TEAM_ELIMINATED);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_DUELS_PLAYER_TEAM_SIZE);

        DisplayStatistic::unregisterStatistic(self::STATISTIC_DUELS_PLAYER_OPPOSITE_TEAM_COLOR);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_DUELS_PLAYER_OPPOSITE_TEAM_PLAYERS_LEFT);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_DUELS_PLAYER_OPPOSITE_TEAM_ELIMINATED);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_DUELS_PLAYER_OPPOSITE_TEAM_SIZE);

        DisplayStatistic::unregisterStatistic(self::STATISTIC_DUELS_TEAM_PLAYER_ELIMINATED);

        DisplayStatistic::unregisterStatistic(self::STATISTIC_SPECTATOR_PLAYER_NAME);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_SPECTATORS_COUNT);
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