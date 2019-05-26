<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-17
 * Time: 23:06
 */

declare(strict_types=1);

namespace practice\scoreboard;


use pocketmine\scheduler\Task;
use practice\PracticeCore;

class UpdateScoreboardTask extends Task
{

    private $player;

    public function __construct($player = null) {
        if(!is_null($player)){
            $playerHandler = PracticeCore::getPlayerHandler();
            if($playerHandler->isPlayerOnline($player))
                $this->player = $playerHandler->getPlayer($player);
        }
    }

    /**
     * Actions to execute when run
     *
     * @param int $currentTick
     *
     * @return void
     */
    public function onRun(int $currentTick) {
        if(!is_null($this->player))
            $this->player->updateScoreboard();
        else ScoreboardUtil::updateSpawnScoreboards();
    }
}