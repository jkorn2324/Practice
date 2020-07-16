<?php

declare(strict_types=1);

namespace jkorn\practice\player\info;


use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;

class ActionsInfo
{
    /** @var int */
    private $currentAction;
    /** @var float|int */
    private $currentActionTime;
    /** @var Position|null */
    private $currentActionPos;

    /** @var int */
    private $lastAction;
    /** @var float|int */
    private $lastActionTime;
    /** @var Position|null */
    private $lastActionPos;

    public function __construct()
    {
        $this->currentActionPos = null;
        $this->currentAction = -1;
        $this->currentActionTime = -1;

        $this->lastActionPos = null;
        $this->lastAction = -1;
        $this->lastActionTime;
    }


    /**
     * @param int $action
     * @param Position|null $pos
     *
     * Sets the action.
     */
    public function setAction(int $action, Position $pos = null) : void {

        $currentTime = microtime(true) * 1000;

        if($this->currentAction === -1) {

            $this->currentAction = $action;
            $this->currentActionTime = $currentTime;
            $this->lastAction = PlayerActionPacket::ACTION_ABORT_BREAK;
            $this->lastActionTime = $currentTime;

            $this->currentActionPos = $pos;
            $this->lastActionPos = $pos;

        } else {

            $this->lastAction = $this->currentAction;
            $this->lastActionPos = $this->currentActionPos;
            $this->lastActionTime = $this->currentActionTime;

            $this->currentAction = $action;
            $this->currentActionTime = $currentTime;
            $this->currentActionPos = $pos;
        }
    }


    /**
     * @return bool
     *
     * Tests if the player did click block or if he was breaking it.
     */
    public function didClickBlock() : bool {

        if($this->lastAction === PlayerActionPacket::ACTION_ABORT_BREAK && $this->currentAction === PlayerActionPacket::ACTION_START_BREAK) {

            $differenceTime = $this->currentActionTime - $this->lastActionTime;
            $differenceVec3 = null;

            if($this->lastActionPos !== null && $this->currentActionPos !== null) {

                if(($lastLevel = $this->lastActionPos->getLevel()) !== null && ($currentLevel = $this->currentActionPos->getLevel()) !== null) {
                    if($lastLevel->getName() !== $currentLevel->getName()) {
                        return false;
                    }
                }

                $differenceVec3 = $this->currentActionPos->subtract($this->lastActionPos)->abs();
            }

            if($differenceTime > 5) {

                if($differenceVec3 instanceof Vector3) {
                    // TODO FIND VALID RETURN STATEMENT
                }

                return true;
            }
        }

        return false;
    }

}