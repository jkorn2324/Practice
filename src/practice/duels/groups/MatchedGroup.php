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

class MatchedGroup
{
    private $playerName;

    private $opponentName;

    private $queue;

    private $ranked;

    public function __construct($player, $opponent, string $queue, bool $ranked = false) {

        $pName = PracticeUtil::getPlayerName($player);
        $oName = PracticeUtil::getPlayerName($opponent);

        if(!is_null($pName)) $this->playerName = $pName;
        if(!is_null($oName)) $this->opponentName = $oName;

        $this->queue = $queue;
        $this->ranked = $ranked;
    }

    public function isRanked() : bool {
        return $this->ranked;
    }

    public function getPlayerName() : string {
        return $this->playerName;
    }

    public function getOpponentName() : string {
        return $this->opponentName;
    }

    public function getPlayer() {
        return PracticeCore::getPlayerHandler()->getPlayer($this->playerName);
    }

    public function getOpponent() {
        return PracticeCore::getPlayerHandler()->getPlayer($this->opponentName);
    }

    public function isPlayerOnline() {
        $p = $this->getPlayer();
        return !is_null($p) and $p->isOnline();
    }

    public function isOpponentOnline() {
        $p = $this->getOpponent();
        return !is_null($p) and $p->isOnline();
    }
    
    public function getQueue() : string {
        return $this->queue;
    }

    public function equals($object) : bool {
        $result = false;
        if($object instanceof MatchedGroup) {
            if($this->getPlayerName() === $object->getPlayerName() and $this->getOpponentName() === $object->getOpponentName()) {
                $result = $this->getQueue() === $object->getQueue();
            }
        }
        return $result;
    }
}