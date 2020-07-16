<?php

declare(strict_types=1);

namespace jkorn\practice\data\providers;

use pocketmine\Player;
use jkorn\practice\data\IDataProvider;

/**
 * Class EmptyDataProvider.
 *
 * This class doesn't do anything except define the default data provider.
 *
 * @package jkorn\practice\data\providers
 */
class EmptyDataProvider implements IDataProvider
{

    /**
     * @param Player $player
     *
     * Loads the player's data.
     */
    public function loadPlayer(Player $player): void {}

    /**
     * @param Player $player
     * @param bool $async - Determines whether to save async or not.
     *
     * Saves the player's data.
     */
    public function savePlayer(Player $player, bool $async): void {}

    /**
     * Saves the data of all the players, used for when the server shuts down.
     */
    public function saveAllPlayers(): void {}
}