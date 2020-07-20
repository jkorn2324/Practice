<?php

declare(strict_types=1);

namespace jkorn\bd\player\team;


use jkorn\practice\games\duels\teams\DuelTeamPlayer;
use jkorn\practice\player\PracticePlayer;

class BasicDuelTeamPlayer extends DuelTeamPlayer
{

    /**
     * Initializes the misc to the player.
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
        if(
            $player instanceof PracticePlayer
            || $player instanceof BasicDuelTeamPlayer
        )
        {
            return $player->getServerID()->equals($this->getServerID());
        }

        return false;
    }
}