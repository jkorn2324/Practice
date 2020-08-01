<?php

declare(strict_types=1);

namespace jkorn\ffa\statistics;


interface FFADisplayStatistics
{

    // The FFA Statistics.
    const STATISTIC_FFA_PLAYERS_PLAYING = "stat.ffa.players.playing";
    const STATISTIC_FFA_ARENA_NAME = "stat.ffa.arena.name";
    const STATISTIC_FFA_ARENA_PLAYERS_PLAYING = "stat.ffa.arena.players.playing";
    const STATISTIC_FFA_ARENA_KIT = "stat.ffa.arena.kit";

    // These statistics are registered in the stats information.
    const STATISTIC_FFA_PLAYER_DEATHS = "stat.ffa.player.deaths";
    const STATISTIC_FFA_PLAYER_KILLS = "stat.ffa.player.kills";
}