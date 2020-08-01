<?php

declare(strict_types=1);

namespace jkorn\practice\games\misc\gametypes;

/**
 * Interface IUpdatedGame
 * @package jkorn\practice\games\misc\gametypes
 *
 * A interface for games that are temporary, sequenced, and
 * are constantly being updated. An example of a game like
 * this is duels.
 */
interface IUpdatedGame extends IGame
{
    /**
     * Updates the game.
     */
    public function update(): bool;

    /**
     * Called to kill the game officially.
     */
    public function die(): void;

    /**
     * @return int
     *
     * Gets the game's status.
     */
    public function getStatus(): int;
}