<?php

declare(strict_types=1);

namespace jkorn\practice\games\duels\types\generic;


use jkorn\practice\games\duels\teams\DuelTeamPlayer;
use jkorn\practice\player\PracticePlayer;

class GenericDuelTeamPlayer extends DuelTeamPlayer
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
            || $player instanceof GenericDuelTeamPlayer
        )
        {
            return $player->getServerID()->equals($this->getServerID());
        }

        return false;
    }
}