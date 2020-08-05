<?php

declare(strict_types=1);

namespace jkorn\practice\games\misc\managers\awaiting;


use jkorn\practice\games\misc\managers\IAwaitingGameManager;
use pocketmine\Player;

/**
 * Interface IAwaitingManager
 * @package jkorn\practice\games\misc
 *
 * Is the interface for an awaiting manager, used by the IAwaitingGameManager
 * class.
 */
interface IAwaitingManager
{

    /**
     * @return IAwaitingGameManager
     *
     * Gets the parent game manager.
     */
    public function getGameManager(): IAwaitingGameManager;

    /**
     * @param callable|null $callable - The callable function.
     * @return int
     *
     * Gets the players waiting for a game.
     */
    public function getPlayersAwaiting(?callable $callable = null): int;

    /**
     * @param Player $player - The player to set as awaiting.
     * @param \stdClass $data - The data corresponding to the duel.
     * @param bool $sendMessage - Determines whether or not to send a message to a player.
     *
     * Sets the player as awaiting for a game.
     */
    public function setAwaiting(Player $player, \stdClass $data, bool $sendMessage = true): void;

    /**
     * @param Player $player - The input player.
     * @return bool - Returns true if player is awaiting, false otherwise.
     *
     * Determines whether the player is waiting for a game.
     */
    public function isAwaiting(Player $player): bool;

    /**
     * @param Player $player
     * @param bool $sendMessage - Determines whether or not to send the player a message.
     *
     * Removes the player from the awaiting players list.
     */
    public function removeAwaiting(Player $player, bool $sendMessage = true): void;
}