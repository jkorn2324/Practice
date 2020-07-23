<?php

declare(strict_types=1);

namespace jkorn\practice\data;


use jkorn\practice\games\IGameManager;
use jkorn\practice\games\misc\leaderboards\LeaderboardGroup;
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

    /**
     * @param IGameManager $gameType - The game type.
     * @param LeaderboardGroup[] $leaderboardGroups
     *
     * Updates the leaderboards based on the input leaderboard groups and the game type.
     */
    public function updateLeaderboards(IGameManager $gameType, $leaderboardGroups): void;
}