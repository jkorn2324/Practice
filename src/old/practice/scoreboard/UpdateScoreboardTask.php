<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-17
 * Time: 23:06
 */

declare(strict_types=1);

namespace old\practice\scoreboard;


use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use old\practice\duels\DuelHandler;
use old\practice\player\PlayerHandler;
use old\practice\player\PracticePlayer;
use old\practice\PracticeCore;

class UpdateScoreboardTask extends Task
{

    private $player;

    public function __construct(PracticePlayer $player = null) {
        if(!is_null($player) and $player->isOnline())
            $this->player = $player;
    }

    /**
     * Actions to execute when run
     * @param int $currentTick;
     * @return void
     */
    public function onRun(int $currentTick) {
        ScoreboardUtil::updateSpawnScoreboards($this->player);
    }
}