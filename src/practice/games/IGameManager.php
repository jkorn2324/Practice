<?php

declare(strict_types=1);

namespace practice\games;


interface IGameManager
{
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

}