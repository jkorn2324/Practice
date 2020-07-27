<?php

declare(strict_types=1);

namespace jkorn\practice\display;

/**
 * Interface DisplayStatisticNames
 * @package jkorn\practice\display
 *
 * Interface that's only use is to contain all of the display statistic names.
 */
interface DisplayStatisticNames
{

    const STATISTIC_ONLINE_PLAYERS = "stat.online.players";
    const STATISTIC_MAX_PLAYERS = "stat.max.players";

    const STATISTIC_PLAYER_PING = "stat.player.ping";
    const STATISTIC_PLAYER_NAME = "stat.player.name";
    const STATISTIC_PLAYER_OS = "stat.player.os";
    const STATISTIC_PLAYER_KDR = "stat.player.kdr";
    const STATISTIC_PLAYER_CPS = "stat.player.cps";
    const STATISTIC_PLAYER_EQUIPPED_KIT = "stat.player.equipped.kit";

    // These are registered via the StatsInfo class.
    const STATISTIC_PLAYER_KILLS = "stat.player.kills";
    const STATISTIC_PLAYER_DEATHS = "stat.player.deaths";

    // The Games Default Statistics
    const STATISTIC_GAMES_TYPE = "stat.games.type";
    const STATISTIC_GAMES_TYPE_PLAYERS_PLAYING = "stat.games.type.players.playing";
    const STATISTIC_GAMES_PLAYERS_PLAYING = "stat.games.players.playing";

    // The FFA Default Statistics
    const STATISTIC_FFA_PLAYERS_PLAYING = "stat.ffa.players.playing";
    const STATISTIC_FFA_ARENA_NAME = "stat.ffa.arena.name";
    const STATISTIC_FFA_ARENA_PLAYERS_PLAYING = "stat.ffa.arena.players.playing";
    const STATISTIC_FFA_ARENA_KIT = "stat.ffa.arena.kit";

    // The kit name statistic.
    const STATISTIC_KIT_NAME = "stat.kit.name";

    // TODO: These statistics.
    const STATISTIC_PLAYER_RANK = "stat.player.rank";
    const STATISTIC_SERVER_TPS = "stat.server.tps";

}