<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-30
 * Time: 21:47
 */

declare(strict_types=1);

namespace practice\player;


use pocketmine\scheduler\Task;
use practice\PracticeCore;
use practice\scoreboard\ScoreboardUtil;

class PlayerSpawnTask extends Task
{
    private $player;

    public function __construct(PracticePlayer $player)
    {
        $this->player = $player;
    }

    /**
     * Actions to execute when run
     *
     * @param int $currentTick
     *
     * @return void
     */
    public function onRun(int $currentTick)
    {
        PracticeCore::getItemHandler()->spawnHubItems($this->player, true);
        ScoreboardUtil::updateSpawnScoreboards($this->player);
    }
}