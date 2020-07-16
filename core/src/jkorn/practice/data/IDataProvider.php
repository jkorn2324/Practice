<?php

declare(strict_types=1);

namespace jkorn\practice\data;


use pocketmine\Player;

interface IDataProvider
{

    /**
     * @param Player $player
     *
     * Loads the player's data.
     */
    public function loadPlayer(Player $player): void;

    /**
     * @param Player $player
     * @param bool $async - Determines whether to save async or not.
     *
     * Saves the player's data.
     */
    public function savePlayer(Player $player, bool $async): void;

    /**
     * Saves the data of all the players, used for when the server shuts down.
     */
    public function saveAllPlayers(): void;
}