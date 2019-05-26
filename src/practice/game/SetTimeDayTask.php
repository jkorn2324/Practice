<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-25
 * Time: 20:10
 */

declare(strict_types=1);

namespace practice\game;


use pocketmine\scheduler\Task;
use practice\PracticeCore;

class SetTimeDayTask extends Task
{

    private $core;

    public function __construct(PracticeCore $core) {
        $this->core = $core;
    }

    /**
     * Actions to execute when run
     *
     * @param int $currentTick
     *
     * @return void
     */
    public function onRun(int $currentTick) {

        $levels = $this->core->getServer()->getLevels();

        foreach($levels as $level) {
            $level->setTime(6000);
            $level->stopTime();
        }
    }
}