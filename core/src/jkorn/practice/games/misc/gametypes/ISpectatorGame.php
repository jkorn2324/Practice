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

    /**
     * @param Player $player - The player used to get the message.
     *
     * @return string
     *
     * Gets the display title of the spectator game,
     * used so that the spectator form could show the game's basic
     * information.
     */
    public function getSpectatorFormDisplay(Player $player): string;

    /**
     * @return string
     *
     * Gets the game's form texture, used so that the form
     * gets pretty printed.
     */
    public function getSpectatorFormTexture(): string;

    /**
     * @return string
     *
     * Gets the game's description, used to display the information
     * on forms to players looking to watch the game.
     */
    public function getGameDescription(): string;

}