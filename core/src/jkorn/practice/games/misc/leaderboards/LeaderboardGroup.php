<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-07-22
 * Time: 16:37
 */

declare(strict_types=1);

namespace jkorn\practice\games\misc\leaderboards;


use jkorn\practice\player\PracticePlayer;

class LeaderboardGroup
{

    /** @var string */
    protected $statistic;

    /** @var array */
    protected $leaderboard = [];

    /** @var bool */
    private $load;

    /** @var string */
    private $displayName;

    public function __construct(string $statistic, string $displayName, bool $load = true)
    {
        $this->statistic = $statistic;
        $this->load = $load;
        $this->displayName = $displayName;
    }

    /**
     * @return string
     *
     * Gets the display name of the leaderboard group.
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * @return string
     *
     * Gets the statistic that we are looking for in the leaderboard group.
     */
    public function getStatistic(): string
    {
        return $this->statistic;
    }

    /**
     * @param array $data
     *
     * Updates the leaderboard group.
     */
    public function update(&$data = []): void
    {
        $this->leaderboard = $data;
    }

    /**
     * @param int $size - The top number of players.
     * @return array
     *
     * Gets the top players in the leaderboard.
     */
    public function getTop(int $size): array
    {
        if(count($this->leaderboard) >= $size)
        {
            return $this->leaderboard;
        }
        elseif($size <= 0)
        {
            return [];
        }

        return array_chunk($this->leaderboard, $size, true)[0];
    }

    /**
     * @param $player
     * @return int
     *
     * Gets the player's rank at a given position.
     */
    public function getPlayerRank($player): int
    {
        $keys = array_keys($this->leaderboard);

        if($player instanceof PracticePlayer)
        {
            if(isset($this->leaderboardsData[$player->getName()]))
            {
                $value = array_search($player->getName(), $keys);
            }
        }
        elseif (is_string($player))
        {
            if(isset($this->leaderboardsData[$player]))
            {
                $value = array_search($player, $keys);
            }
        }

        if(isset($value))
        {
            if(is_bool($value))
            {
                return count($this->leaderboard);
            }

            return $value;
        }

        return count($this->leaderboard);
    }

    /**
     * @return bool
     *
     * Determines whether the leaderboard group loads from player data or
     * whether it is a statistic that doesn't exist in player statistic data.
     */
    public function doLoad(): bool
    {
        return $this->load;
    }
}