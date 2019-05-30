<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-29
 * Time: 09:35
 */

namespace practice\duels\groups;

use practice\player\PracticePlayer;
use practice\PracticeCore;
use practice\PracticeUtil;

class Request
{

    private const MAX_WAIT_SECONDS = 120;

    private $player;

    private $requested;

    private $queue;

    private $secsFromRequest;

    public function __construct($requestor, $requested, string $queue) {

        if(!is_null(PracticeUtil::getPlayerName($requestor))) {
            $this->player = PracticeUtil::getPlayerName($requestor);
        }

        if(!is_null(PracticeUtil::getPlayerName($requested))) {
            $this->requested = PracticeUtil::getPlayerName($requested);
        }

        $this->secsFromRequest = 0;

        $this->queue = $queue;
    }

    public function update() : bool {

        $this->secsFromRequest++;

        $delete = false;
        $max = self::MAX_WAIT_SECONDS;

        if($this->secsFromRequest >= $max) {
            $delete = true;
        } elseif (!$this->isRequestedOnline() or !$this->isRequestorOnline())
            $delete = true;

        return $delete;
    }

    public function getQueue() : string {
        return $this->queue;
    }

    public function getRequestorName() : string {
        return $this->player;
    }

    public function getRequestedName() : string {
        return $this->requested;
    }

    public function getRequestor() {
        return PracticeCore::getPlayerHandler()->getPlayer($this->player);
    }

    public function getRequested() {
        return PracticeCore::getPlayerHandler()->getPlayer($this->requested);
    }

    public function isRequestorOnline() : bool {
        return PracticeCore::getPlayerHandler()->isPlayerOnline($this->player);
    }

    public function isRequestedOnline() : bool {
        return PracticeCore::getPlayerHandler()->isPlayerOnline($this->requested);
    }

    public function isTheRequestor($player) : bool {
        $result = false;
        if(PracticeCore::getPlayerHandler()->isPlayerOnline($player)) {
            $p = PracticeCore::getPlayerHandler()->getPlayer($player);
            if($this->isRequestorOnline()) {
                $result = $this->getRequestor()->equals($p);
            }
        }
        return $result;
    }

    public function isTheRequested($player) : bool {
        $result = false;
        if(PracticeCore::getPlayerHandler()->isPlayerOnline($player)) {
            $p = PracticeCore::getPlayerHandler()->getPlayer($player);
            if($this->isRequestedOnline()) {
                $result = $this->getRequestor()->equals($p);
            }
        }
        return $result;
    }

    public function setExpired() : void {

        if($this->isRequestorOnline()) {
            $p = $this->getRequestor();
            if(!$p->isInDuel() and !PracticeCore::getDuelHandler()->isWaitingForDuelToStart($p->getPlayer())) {
                $p->sendMessage(PracticeUtil::str_replace(PracticeUtil::getMessage("duels.1vs1.result-msg"), ["%player%" => $this->getRequestedName(), "%accept%" => "declined", "%ranked% " => "", "%kit%" => $this->queue, "%msg%" => ""]));
            }
        }

        if($this->isRequestedOnline()) {
            $p = $this->getRequested();
            if(!$p->isInDuel() and !PracticeCore::getDuelHandler()->isWaitingForDuelToStart($p->getPlayer())) {
                $p->sendMessage(PracticeUtil::str_replace(PracticeUtil::getMessage("duels.1vs1.fail-no-time"), ["%player%" => $this->getRequestorName()]));
            }
        }
    }

    public function canAccept() : bool {

        $result = false;

        if($this->isRequestedOnline()) {
            $msg = null;
            $requested = $this->getRequested();
            if($this->isRequestorOnline()) {
                $player = $this->getRequestor();
                $result = !$requested->equals($player);
            } else {
                $msg = PracticeUtil::getMessage("not-online");
                $msg = strval(str_replace("%player-name%", $this->player, $msg));
            }

            if(!is_null($msg)) $requested->sendMessage($msg);
        }

        return $result;
    }

    public static function canSend(PracticePlayer $p, $requestedPlayer) : bool {
        $result = false;
        $msg = null;
        $requested = strval($requestedPlayer);

        if(PracticeCore::getPlayerHandler()->isPlayerOnline($requested)) {
            $rq = PracticeCore::getPlayerHandler()->getPlayer($requested);
            if(!$rq->equals($p))
                $result = PracticeUtil::canRequestPlayer($p->getPlayer(), $rq);
            else $msg = PracticeUtil::getMessage("duels.misc.fail-yourself");

        } else {
            $msg = PracticeUtil::getMessage("not-online");
            $msg = strval(str_replace("%player-name%", $requested, $msg));
        }

        if(!is_null($msg)) $p->sendMessage($msg);

        return $result;
    }

    public function equals($object) : bool {

        $result = false;

        if($object instanceof Request) {

            $rqName = $object->getRequestedName();
            $plName = $object->getRequestorName();

            $result = $rqName === $this->requested and $plName === $this->player;
        }

        return $result;
    }
}