<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-22
 * Time: 13:37
 */

declare(strict_types=1);

namespace practice\duels\groups;

use pocketmine\Player;
use practice\player\PracticePlayer;
use practice\PracticeCore;
use practice\PracticeUtil;

class QueuedPlayer
{
    private $playerName;

    private $queue;

    private $ranked;

    private $peOnly;

    public function __construct(string $name, string $queue, bool $ranked = false, bool $peOnly = false) {
        $this->playerName = $name;
        $this->queue = $queue;
        $this->ranked = $ranked;
        $this->peOnly = $peOnly;
    }

    public function isPEOnly() : bool {
        return $this->peOnly;
    }

    public function getQueue() : string {
        return $this->queue;
    }

    public function isRanked() : bool {
        return $this->ranked;
    }

    public function getPlayerName() : string {
        return $this->playerName;
    }

    public function getPlayer() {
        return PracticeCore::getPlayerHandler()->getPlayer($this->playerName);
    }

    public function isPlayerOnline() : bool {
        return !is_null($this->getPlayer()) and $this->getPlayer()->isOnline();
    }

    public function hasSameQueue(QueuedPlayer $player) : bool {
        $result = false;
        if($player->getQueue() === $this->queue) {
            $ranked = $player->isRanked();
            $result = $this->ranked === $ranked;
        }
        return $result;
    }

    public function equals($object) : bool {
        $result = false;
        if($object instanceof QueuedPlayer) {
            if ($object->getPlayerName() === $this->playerName) {
                $result = true;
            }
        }
        return $result;
    }

    public function toString() : string {

        $str = PracticeUtil::getName('scoreboard.spawn.thequeue');

        $ranked = ($this->ranked === true) ? 'Ranked' : 'Unranked';

        return PracticeUtil::str_replace($str, ['%ranked%' => $ranked, '%queue%' => $this->queue]);
    }
}