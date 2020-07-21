<?php

declare(strict_types=1);

namespace jkorn\bd\player;


use jkorn\practice\games\duels\DuelPlayer;
use jkorn\practice\player\PracticePlayer;

class BasicDuelPlayer extends DuelPlayer
{
    /**
     * Initializes the properties to the player.
     * Unused in Basic Duel Player.
     */
    protected function initProperties(): void {}

    /**
     * @param $player
     * @return bool
     *
     * Determines if another player is equivalent.
     */
    public function equals($player): bool
    {
        if($player instanceof PracticePlayer || $player instanceof BasicDuelPlayer)
        {
            return $player->getServerID()->equals($this->getServerID());
        }

        return false;
    }
}