<?php

declare(strict_types=1);

namespace jkorn\practice\games\misc\gametypes;


use jkorn\practice\games\misc\gametypes\IGame;
use pocketmine\Player;

interface ISpectatorGame extends IGame
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
     * @param bool $broadcast
     *
     * Adds the spectator to the game.
     */
    public function addSpectator(Player $player, bool $broadcast = true): void;

    /**
     * @param Player $player - The player being removed.
     * @param bool $broadcastMessage - Broadcasts the message.
     * @param bool $teleportToSpawn - Determines whether or not to teleport to spawn.
     *
     * Removes the spectator from the game.
     */
    public function removeSpectator(Player $player, bool $broadcastMessage = true, bool $teleportToSpawn = true): void;

    /**
     * @param callable $callable - Requires a player parameter.
     *      EX: function(Player $player) {}
     *
     * Broadcasts something to the spectators.
     */
    public function broadcastSpectators(callable $callable): void;

    /**
     * @return int
     *
     * Gets the spectator count of the game.
     */
    public function getSpectatorCount(): int;
}