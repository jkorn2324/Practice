<?php

declare(strict_types=1);

namespace jkorn\practice\games\misc\managers;

/**
 * Interface IUpdatedGameManager
 * @package jkorn\practice\games\misc\managers
 *
 * Used for games that require updating or temporary games.
 */
interface IUpdatedGameManager extends IGameManager
{

    /**
     * @param mixed ...$args - The arguments needed to create a new game.
     *
     * The arguments needed to create a new game.
     */
    public function create(...$args): void;

    /**
     * @param $game
     *
     * Removes the game from the manager.
     */
    public function remove($game): void;

    /**
     * @param int $currentTick
     *
     * Updates all of the games in the game manager.
     */
    public function update(int $currentTick): void;
}