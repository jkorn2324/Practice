<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-19
 * Time: 15:10
 */

namespace practice\arenas;


use pocketmine\scheduler\Task;
use practice\player\PracticePlayer;

class TeleportArenaTask extends Task
{

    private $player;

    private $arena;

    public function __construct(PracticePlayer $player, FFAArena $arena) {
        $this->player = $player;
        $this->arena = $arena;
    }

    /**
     * Actions to execute when run
     *
     * @param int $currentTick
     *
     * @return void
     */
    public function onRun(int $currentTick) {
        $this->player->teleportToFFA($this->arena);
    }
}