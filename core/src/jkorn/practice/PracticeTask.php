<?php

declare(strict_types=1);

namespace jkorn\practice;


use jkorn\practice\games\misc\managers\IUpdatedGameManager;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class PracticeTask extends Task
{

    /** @var PracticeCore */
    private $core;
    /** @var Server */
    private $server;

    /** @var int - The current tick of the task. */
    private $currentTick = 0;

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
        $this->updateGames();

        $this->currentTick++;
    }

    /**
     * Updates all of the games.
     */
    private function updateGames(): void
    {
        $games = PracticeCore::getBaseGameManager()->getGameTypes();

        foreach($games as $game)
        {
            if($game instanceof IUpdatedGameManager)
            {
                $game->update($this->currentTick);
            }
        }
    }
}