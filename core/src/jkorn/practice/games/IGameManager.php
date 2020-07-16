<?php

declare(strict_types=1);

namespace jkorn\practice\games;


interface IGameManager
{

    const MANAGER_GENERIC_DUELS = "generic.duels";

    /**
     * Called when the game manager is first registered.
     */
    public function onRegistered(): void;

    /**
     * Called when the game manager is unregistered.
     */
    public function onUnregistered(): void;

    /**
     * @param $game
     *
     * Removes the game from the list.
     */
    public function remove($game): void;

    /**
     * Updates the game manager.
     * @param int $currentTick
     */
    public function update(int $currentTick): void;

    /**
     * @return string
     *
     * Gets the type of game manager.
     */
    public function getType(): string;

    /**
     * @return string
     *
     * Gets the title of the type of game.
     */
    public function getTitle(): string;
}