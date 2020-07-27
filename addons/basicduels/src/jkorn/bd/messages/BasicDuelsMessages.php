<?php

declare(strict_types=1);

namespace jkorn\bd\messages;

/**
 * Interface BasicDuelsMessages
 * @package jkorn\bd\messages
 *
 * Gets the list of all the basic duels messages.
 */
interface BasicDuelsMessages
{

    // The countdown seconds titles.
    const COUNTDOWN_SECONDS_TITLE_FIVE = "duels.basic.countdown.title.seconds.5";
    const COUNTDOWN_SECONDS_TITLE_FOUR_THRU_ONE = "duels.basic.countdown.title.seconds.4-1";
    const COUNTDOWN_SECONDS_TITLE_BEGIN = "duels.basic.countdown.title.begin";

    // The result messages for the winner, loser, and spectators for 1vs1 duels.
    const DUELS_1VS1_RESULT_MESSAGE_WINNER = "duels.basic.1vs1.result.message.winner";
    const DUELS_1VS1_RESULT_MESSAGE_LOSER = "duels.basic.1vs1.result.message.loser";
    const DUELS_1VS1_RESULT_MESSAGE_SPECTATORS = "duels.basic.1vs1.result.message.spectator";
    const DUELS_1VS1_RESULT_MESSAGE_DRAW = "duels.basic.1vs1.result.message.draw";

    const DUELS_TEAMS_RESULT_MESSAGE_WINNING_TEAM = "duels.basic.team.result.message.winning";
    const DUELS_TEAMS_RESULT_MESSAGE_LOSING_TEAM = "duels.basic.team.result.message.losing";
    const DUELS_TEAMS_RESULT_MESSAGE_SPECTATORS = "duels.basic.team.result.message.spectator";
    const DUELS_TEAMS_RESULT_MESSAGE_FAILED = "duels.basic.team.result.message.failed";

    // TODO: Add statistics.
    const DUELS_TEAMS_MESSAGE_PLAYER_ELIMINATED = "duels.basic.team.result.message.eliminated";
}