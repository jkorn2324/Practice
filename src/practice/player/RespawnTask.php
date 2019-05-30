<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-30
 * Time: 15:51
 */

declare(strict_types=1);

namespace practice\player;

use pocketmine\scheduler\Task;
use practice\PracticeUtil;

class RespawnTask extends Task
{

    private $player;

    public function __construct(PracticePlayer $p)
    {
        $this->player = $p;
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
        PracticeUtil::respawnPlayer($this->player);
    }
}