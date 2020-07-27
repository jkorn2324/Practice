<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-07-22
 * Time: 17:45
 */

declare(strict_types=1);

namespace jkorn\bd\duels\leaderboard;

use pocketmine\Server;
use jkorn\bd\BasicDuelsUtils;
use jkorn\practice\games\misc\leaderboards\LeaderboardGroup;

class WLRatioGroup extends LeaderboardGroup
{


    /** @var LeaderboardGroup */
    private $wins, $losses;

    public function __construct(LeaderboardGroup $kills, LeaderboardGroup $deaths)
    {
        parent::__construct(BasicDuelsUtils::STATISTIC_DUELS_PLAYER_WL_RATIO, "Basic Duels W/L Ratio", false);

        $this->wins = $kills;
        $this->losses = $deaths;
    }

    /**
     * @param array $data
     *
     * Updates the group based on the data, however in this case
     * we don't use the data input and we instead loop through wins
     * and losses and update based on that.
     */
    public function update(&$data = []): void
    {
        // TODO: See if this causes lots of memory issues, try find a more efficient way of doing this.
        $killsLeaderboard = $this->wins->leaderboard;

        foreach($killsLeaderboard as $player => $wins)
        {
            $losses = $this->losses->leaderboard[$player];
            if($losses <= 0)
            {
                $losses = 1;
            }

            $this->leaderboard[$player] = (float)$wins / (float)$losses;
        }

        asort($this->leaderboard);
    }
}