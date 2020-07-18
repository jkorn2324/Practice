<?php

declare(strict_types=1);

namespace jkorn\practice\games\duels\types\generic;


use jkorn\practice\games\duels\DuelPlayer;
use jkorn\practice\player\PracticePlayer;

class GenericDuelPlayer extends DuelPlayer
{
    /**
     * Initializes the misc to the player.
     */
    protected function initProperties(): void
    {
        // TODO: Implement initProperties() method.
    }

    /**
     * @param $player
     * @return bool
     *
     * Determines if another player is equivalent.
     */
    public function equals($player): bool
    {
        if($player instanceof PracticePlayer || $player instanceof GenericDuelPlayer)
        {
            return $player->getServerID()->equals($this->getServerID());
        }

        return false;
    }
}