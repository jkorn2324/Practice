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

    const STATISTIC_SERVER_CURRENT_TPS = "stat.server.current.tps";
    const STATISTIC_SERVER_AVERAGE_TPS = "stat.server.average.tps";
    const STATISTIC_SERVER_CURRENT_LOAD = "stat.server.current.load";
    const STATISTIC_SERVER_AVERAGE_LOAD = "stat.server.average.load";

    const STATISTIC_PLAYER_PING = "stat.player.ping";
    const STATISTIC_PLAYER_NAME = "stat.player.name";
    const STATISTIC_PLAYER_OS = "stat.player.os";
    const STATISTIC_TOTAL_PLAYER_KDR = "stat.player.kdr.total";
    const STATISTIC_PLAYER_CPS = "stat.player.cps";
    const STATISTIC_PLAYER_EQUIPPED_KIT = "stat.player.equipped.kit";

    // These are registered via the StatsInfo class.
    const STATISTIC_TOTAL_PLAYER_KILLS = "stat.player.kills.total";
    const STATISTIC_TOTAL_PLAYER_DEATHS = "stat.player.deaths.total";

    // The Games Default Statistics
    const STATISTIC_GAMES_TYPE = "stat.games.type";
    const STATISTIC_GAMES_TYPE_PLAYERS_PLAYING = "stat.games.type.players.playing";
    const STATISTIC_GAMES_PLAYERS_PLAYING = "stat.games.players.playing";

    // TODO: These statistics.
    const STATISTIC_PLAYER_RANK = "stat.player.rank";

}