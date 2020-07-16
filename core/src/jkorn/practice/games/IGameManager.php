<?php

declare(strict_types=1);

namespace jkorn\practice\games;


interface IGameManager
{

    const MANAGER_GENERIC_DUELS = "generic.duels";

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