<?php

declare(strict_types=1);

namespace jkorn\bd\player\team;


use jkorn\bd\arenas\IDuelArena;
use jkorn\practice\games\duels\teams\DuelTeam;
use jkorn\practice\kits\IKit;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;

class BasicDuelTeam extends DuelTeam
{
    // Used for generic duels.
    const TEAM_1 = 0;
    const TEAM_2 = 1;

    /**
     * @param mixed ...$extraData, for this specific duel team, it requires
     * 2 parameters: The teamNumber, arena, kit, and level.
     * EX: putPlayersInGame($teamNumber, $arena, $kit);
     *
     * Puts the player in the game.
     */
    public function putPlayersInGame(...$extraData): void
    {
        if(count($extraData) < 2)
        {
            return;
        }

        /** @var int $teamNumber */
        $teamNumber = $extraData[0];
        /** @var IDuelArena $arena */
        $arena = $extraData[1];
        /** @var IKit $kit */
        $kit = $extraData[2];
        /** @var Level $level */
        $level = $extraData[3];

        // Determines whether or not to place team positions using x axis.
        $xAxis = $this->placeTeamsOnXAxis($arena, $teamNumber);

        // Gets the start position of the players.
        $position = $teamNumber === self::TEAM_1 ? $arena->getP1StartPosition() : $arena->getP1StartPosition();
        $position = new Position($position->x, $position->y, $position->z, $level);

        foreach($this->players as $player)
        {
            if($player->isOnline())
            {
                $rawPlayer = $player->getPlayer();

                $rawPlayer->setGamemode(0);
                $rawPlayer->setImmobile(true);
                $rawPlayer->clearInventory();
            }
        }
    }

    /**
     * @param IDuelArena $arena
     * @param int $teamNumber
     * @return bool
     *
     * Determines whether we want to place players corresponding to the x or z axis.
     */
    protected function placeTeamsOnXAxis(IDuelArena &$arena, int $teamNumber): bool
    {
        if($teamNumber === self::TEAM_1)
        {
            $position = $arena->getP1StartPosition();
            $opponentPosition = $arena->getP2StartPosition();
        }
        else
        {
            $position = $arena->getP2StartPosition();
            $opponentPosition = $arena->getP1StartPosition();
        }
        $xDifference = abs($position->x - $opponentPosition->x);
        $zDifference = abs($position->z - $opponentPosition->z);
        return $xDifference >= $zDifference;
    }

    /**
     * @param Position $position - The start position of the player.
     * @param int $index - The index of the player.
     * @param bool $xAxis - Determines which axis we are determining the position of.
     * @return Position
     *
     * Determines the position based on the player's index.
     */
    protected function determinePosition(Position &$position, int $index, bool $xAxis): Position
    {
        $teamSize = $this->getTeamSize();
        if($teamSize % 2 == 0)
        {
            $half = $teamSize / 2;
            if($index < $half)
            {
                // TODO
            }
        }
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
        return false;
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