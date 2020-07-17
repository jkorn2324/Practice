<?php

declare(strict_types=1);

namespace jkorn\practice\games\duels\types\generic;


use jkorn\practice\games\duels\teams\DuelTeam;
use pocketmine\Player;

class GenericDuelTeam extends DuelTeam
{
    /**
     * @param mixed ...$extraData
     *
     * Puts the player in the game.
     */
    public function putPlayersInGame(...$extraData): void
    {
        // TODO: Implement putPlayersInGame() method.
    }

    /**
     * @param Player $player
     * @param mixed ...$extraData - The extraData containing the reason why the player was eliminated.
     * @return bool - Returns true if all members are eliminated.
     *
     * Eliminates the player.
     */
    public function eliminate(Player $player, ...$extraData): bool
    {
        // TODO: Implement eliminate() method.
    }

    /**
     * @param $object
     * @return bool
     *
     * Determines if the team is equivalent to another.
     */
    public function equals($object): bool
    {
        // TODO: Implement equals() method.
        return false;
    }
}