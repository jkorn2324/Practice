<?php

declare(strict_types=1);

namespace practice\arenas\types\duels;


use pocketmine\math\Vector3;

interface IDuelArena
{

    /**
     * @return Vector3
     *
     * Gets the first player position.
     */
    public function getPlayer1Position(): Vector3;

    /**
     * @return Vector3
     *
     * Gets the second player position.
     */
    public function getPlayer2Position(): Vector3;

    /**
     * @param $kit
     * @return bool
     *
     * Determines if the kit is valid.
     */
    public function isValidKit($kit): bool;

    /**
     * @param Vector3 $position
     * @return bool
     *
     * Determines whether or not the player is in the arena.
     */
    public function isWithinArena(Vector3 $position): bool;
}