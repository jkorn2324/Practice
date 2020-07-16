<?php

declare(strict_types=1);

namespace jkorn\practice\games\misc;


use pocketmine\Player;

interface ISpectatorGame
{

    /**
     * @param Player $player
     * @return bool
     *
     * Determines if the player is a spectator.
     */
    public function isSpectator(Player $player): bool;

    /**
     * @param Player $player
     *
     * Adds the spectator to the game.
     */
    public function addSpectator(Player $player): void;

    /**
     * @param Player $player - The player being removed.
     * @param bool $broadcastMessage - Broadcasts the message.
     * @param bool $teleportToSpawn - Determines whether or not to teleport to spawn.
     *
     * Removes the spectator from the game.
     */
    public function removeSpectator(Player $player, bool $broadcastMessage = true, bool $teleportToSpawn = true): void;
}