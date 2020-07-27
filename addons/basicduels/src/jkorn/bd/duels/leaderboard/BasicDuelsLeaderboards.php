<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-07-22
 * Time: 16:11
 */

declare(strict_types=1);

namespace jkorn\bd\duels\leaderboard;

use jkorn\bd\BasicDuelsUtils;
use jkorn\practice\data\PracticeDataManager;
use jkorn\practice\games\misc\leaderboards\LeaderboardGroup;
use pocketmine\Server;
use jkorn\bd\BasicDuelsManager;
use jkorn\practice\games\IGameManager;
use jkorn\practice\games\misc\leaderboards\IGameLeaderboard;

class BasicDuelsLeaderboards implements IGameLeaderboard
{

    // TWO MINUTES
    const LEADERBOARD_UPDATE_SECONDS = 120;

    /** @var BasicDuelsManager */
    private $parent;

    /** @var Server */
    private $server;

    /** @var LeaderboardGroup[] */
    private $groups = [];

    public function __construct(BasicDuelsManager $manager)
    {
        $this->parent = $manager;
        $this->server = Server::getInstance();

        $this->initGroups();
    }

    /**
     * Initializes the duels leaderboard groups.
     */
    private function initGroups(): void
    {
        $this->addGroup($wins = new LeaderboardGroup(BasicDuelsUtils::STATISTIC_DUELS_PLAYER_WINS, "Basic Duel Wins"));
        $this->addGroup($losses = new LeaderboardGroup(BasicDuelsUtils::STATISTIC_DUELS_PLAYER_LOSSES, "Basic Duel Losses"));
        $this->addGroup(new WLRatioGroup($wins, $losses));
    }

    /**
     * @return IGameManager
     *
     * Gets the parent of the leaderboard manager.
     */
    public function getParent(): IGameManager
    {
        return $this->parent;
    }

    /**
     * Updates the game leaderboard.
     */
    public function update(): void
    {
        PracticeDataManager::getDataProvider()->updateLeaderboards(
            $this->parent, $this->groups);
    }

    /**
     * @param array $data
     *
     * Called after the leaderboard finishes updating.
     */
    public function finishUpdate(array $data): void
    {
        foreach($this->groups as $statistic => $group)
        {
            if(isset($data[$statistic]))
            {
                $this->groups[$statistic]->update($data[$statistic]);
            }
            else
            {
                // Updates non loaded statistics.
                $this->groups[$statistic]->update();
            }
        }
    }

    /**
     * @param LeaderboardGroup $group
     *
     * Adds the leaderboard group.
     */
    public function addGroup(LeaderboardGroup $group): void
    {
        $this->groups[$group->getStatistic()] = $group;
    }

    /**
     * @param string $statistic
     * @return LeaderboardGroup|null
     *
     * Gets the leaderboard group based on the statistic.
     */
    public function getLeaderboardGroup(string $statistic): ?LeaderboardGroup
    {
        if(isset($this->groups[$statistic]))
        {
            return $this->groups[$statistic];
        }

        return null;
    }

    /**
     * @return LeaderboardGroup[]
     *
     * Gets all of the leaderboard groups.
     */
    public function getGroups()
    {
        return $this->groups;
    }
}