<?php

declare(strict_types=1);

namespace jkorn\practice;


use pocketmine\scheduler\Task;
use pocketmine\Server;

class PracticeTask extends Task
{

    /** @var PracticeCore */
    private $core;
    /** @var Server */
    private $server;

    public function __construct(PracticeCore $core)
    {
        $this->core = $core;
        $this->server = $core->getServer();

        $core->getScheduler()->scheduleRepeatingTask($this, 1);
    }

    /**
     * Actions to execute when run
     *
     * @param int $currentTick
     * @return void
     */
    public function onRun(int $currentTick)
    {
        // TODO: Implement onRun() method.
    }
}