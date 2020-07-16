<?php

declare(strict_types=1);

namespace jkorn\practice\games;


use pocketmine\Player;

interface IGame
{
    const REASON_DIED = 0;
    const REASON_LEFT_SERVER = 1;
    const REASON_UNFAIR_RESULT = 2;

    /**
     * Updates the game.
     */
    public function update(): bool;

    /**
     * Called to kill the game officially.
     */
    public function die(): void;

    /**
     * @param $player - The player.
     * @return bool
     *
     * Determines if the player is playing.
     */
    public function isPlaying($player): bool;

    /**
     * @param Player $player
     * @param int $reason
     *
     * Removes the player from the game based on the reason.
     */
    public function removeFromGame(Player $player, int $reason): void;

    /**
     * @param $game
     * @return bool
     *
     * Determines if the game is equivalent.
     */
    public function equals($game): bool;

    /**
     * @return int
     *
     * Gets the game's id.
     */
    public function getID(): int;
}