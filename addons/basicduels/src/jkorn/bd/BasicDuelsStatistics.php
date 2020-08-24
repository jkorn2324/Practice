<?php

declare(strict_types=1);

namespace jkorn\bd;


/**
 * Interface BasicDuelsStatistics
 * @package jkorn\bd
 *
 * Contains all of the constants for statistics.
 */
interface BasicDuelsStatistics
{

    // Stats that are registered in the statsInfo class.
    const STATISTIC_DUELS_PLAYER_WINS = "duels.basic.stat.wins";
    const STATISTIC_DUELS_PLAYER_LOSSES = "duels.basic.stat.losses";
    // Gets the player's current win streak.
    const STATISTIC_DUELS_PLAYER_WIN_STREAK = "duels.basic.stat.win.streak";

    const STATISTIC_DUELS_PLAYER_WL_RATIO = "duels.basic.stat.wl.ratio";

    const STATISTIC_DUEL_TYPE_AWAITING_PLAYERS = "duels.basic.stat.type.awaiting.players";
    const STATISTIC_DUEL_TYPE_AWAITING_KIT_PLAYERS = "duels.basic.stat.type.kit.awaiting.players";

    const STATISTIC_DUEL_GAME_TYPE = "duels.basic.stat.type";
    const STATISTIC_DUEL_KIT = "duels.basic.stat.kit";

    const STATISTIC_PLAYER_DUEL_GAME_TYPE = "duels.basic.stat.type.player";
    const STATISTIC_PLAYER_DUEL_KIT = "duels.basic.stat.kit.player";

    const STATISTIC_DUELS_COUNTDOWN_SECONDS = "duels.basic.countdown.seconds";

    const STATISTIC_DUELS_PLAYER_OPPONENT_NAME = "duels.basic.stat.player.opponent.name";
    const STATISTIC_DUELS_PLAYER_OPPONENT_PING = "duels.basic.stat.player.opponent.ping";
    const STATISTIC_DUELS_PLAYER_OPPONENT_CPS = "duels.basic.stat.player.opponent.cps";

    const STATISTIC_DUELS_PLAYER_TEAM_COLOR = "duels.basic.stat.player.team.color";
    const STATISTIC_DUELS_PLAYER_TEAM_ELIMINATED = "duels.basic.stat.player.team.eliminated";
    const STATISTIC_DUELS_PLAYER_TEAM_PLAYERS_LEFT = "duels.basic.stat.player.team.players.left";
    const STATISTIC_DUELS_PLAYER_TEAM_SIZE = "duels.basic.stat.player.team.size";

    const STATISTIC_DUELS_TEAM_PLAYER_ELIMINATED = "duels.basic.team.stat.eliminated.player.name";

    const STATISTIC_DUELS_PLAYER_OPPOSITE_TEAM_COLOR = "duels.basic.stat.player.opposite.team.color";
    const STATISTIC_DUELS_PLAYER_OPPOSITE_TEAM_ELIMINATED = "duels.basic.stat.player.opposite.team.eliminated";
    const STATISTIC_DUELS_PLAYER_OPPOSITE_TEAM_PLAYERS_LEFT = "duels.basic.stat.player.opposite.team.players.left";
    const STATISTIC_DUELS_PLAYER_OPPOSITE_TEAM_SIZE = "duels.basic.stat.player.opposite.team.size";

    const STATISTIC_DUELS_DURATION = "duels.basic.stat.duration";

    const STATISTIC_DUELS_1VS1_WINNER_NAME = "duels.basic.1vs1.stat.winner.name";
    const STATISTIC_DUELS_1VS1_LOSER_NAME = "duels.basic.1vs1.stat.loser.name";

    // Result teams statistics.
    const STATISTIC_DUELS_TEAMS_WINNING_TEAM_COLOR = "duels.basic.team.stat.winner.color";
    const STATISTIC_DUELS_TEAMS_LOSING_TEAM_COLOR = "duels.basic.team.stat.loser.color";

    // The spectator name of the player.
    const STATISTIC_SPECTATOR_PLAYER_NAME = "duels.basic.stat.spectator.name";
    const STATISTIC_SPECTATORS_COUNT = "duels.basic.stat.spectator.count";

}