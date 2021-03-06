<?php

declare(strict_types=1);

namespace jkorn\practice\messages;


interface IPracticeMessages
{

    const PLAYER_JOIN_MESSAGE = "player.join.message";
    const PLAYER_LEAVE_MESSAGE = "player.leave.message";

    const PLAYER_KIT_EQUIP_MESSAGE = "player.kit.equip.message";

    // TODO: Remove FFA Message.
    const PLAYER_FFA_ARENA_JOIN_MESSAGE = "player.ffa.arena.join.message";
}