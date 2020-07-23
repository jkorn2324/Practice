<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-07-22
 * Time: 15:56
 */

declare(strict_types=1);

namespace jkorn\practice\games\misc\leaderboards;


use jkorn\practice\games\IGameManager;

interface IGameLeaderboard
{
    /**
     * @return IGameManager
     *
     * Gets the parent of the leaderboard manager.
     */
    public function getParent(): IGameManager;

    /**
     * Updates the game leaderboard.
     */
    public function update(): void;

    /**
     * @param array $data
     *
     * Called after the leaderboard finishes updating.
     */
    public function finishUpdate(array $data): void;


    /**
     * @param LeaderboardGroup $group
     *
     * Adds the leaderboard group.
     */
    public function addGroup(LeaderboardGroup $group): void;

    /**
     * @param string $statistic
     * @return LeaderboardGroup|null
     *
     * Gets the leaderboard group based on the statistic.
     */
    public function getLeaderboardGroup(string $statistic): ?LeaderboardGroup;

    /**
     * @return LeaderboardGroup[]
     *
     * Gets all of the groups.
     */
    public function getGroups();
}