<?php

declare(strict_types=1);

namespace jkorn\practice\arenas\types\duels;

use pocketmine\level\Level;
use pocketmine\math\Vector3;
use jkorn\practice\arenas\PracticeArena;
use pocketmine\Server;

/**
 * Class PostGeneratedDuelArena
 * @package jkorn\practice\arenas\types\duels
 *
 * A duel arena that is post generated.
 */
class PostGeneratedDuelArena extends PracticeArena implements IDuelArena
{

    public function __construct(string $levelName)
    {
        parent::__construct(strtolower($levelName), Server::getInstance()->getLevelByName($levelName));
    }

    /**
     * @return Vector3
     *
     * Gets the first player position.
     */
    public function getP1StartPosition(): Vector3
    {
        // TODO: Implement getPlayer1Position() method.
    }

    /**
     * @return Vector3
     *
     * Gets the second player position.
     */
    public function getP2StartPosition(): Vector3
    {
        // TODO: Implement getPlayer2Position() method.
    }

    /**
     * @param $arena
     * @return bool
     *
     * Determines if the arena is a post generated arena.
     */
    public function equals($arena): bool
    {
        if($arena instanceof PostGeneratedDuelArena)
        {
            return $arena->getLocalizedName() === $this->localizedName;
        }

        return false;
    }

    /**
     * @param $kit
     * @return bool
     *
     * Determines if the kit is valid.
     */
    public function isValidKit($kit): bool
    {
        // TODO: Implement isValidKit() method.
    }

    /**
     * @param Vector3 $position
     * @return bool
     *
     * Determines whether or not the player is in the arena.
     */
    public function isWithinArena(Vector3 $position): bool
    {
        // TODO: Implement isWithinArena() method.
    }
}