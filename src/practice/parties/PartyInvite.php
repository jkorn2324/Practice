<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-24
 * Time: 15:03
 */

declare(strict_types=1);

namespace practice\parties;

use pocketmine\utils\TextFormat;
use practice\PracticeCore;
use practice\PracticeUtil;

class PartyInvite
{

    private $sender;

    private $invited;

    //private $tick;

    private $seconds;

    private static $INVITEIDS = 0;

    private $id;

    private const MAX_INVITE_SECS = 20;

    public function __construct(string $sender, string $invited) {
        $this->sender = $sender;
        $this->invited = $invited;
        //$this->tick = 0;
        $this->seconds = 0;
        $this->id = self::$INVITEIDS;
        self::$INVITEIDS++;
    }

    public function getID() : int {
        return $this->id;
    }

    public function getSender() : string {
        return $this->sender;
    }

    public function getInvited() : string {
        return $this->invited;
    }

    public function update() : bool {

        $remove = false;

        if($this->seconds > self::MAX_INVITE_SECS or !$this->arePlayersOnline()) {
            $remove = true;
            $this->setExpired();
        }

        /*$ticks = PracticeUtil::secondsToTicks(self::MAX_INVITE_SECS);

        if($this->tick > $ticks or !$this->arePlayersOnline()) {
            $remove = true;
            $this->setExpired();
        }*/

        $this->seconds++;

        //$this->tick++;

        return $remove;
    }

    private function setExpired() : void {

        $senderMsg = PracticeUtil::str_replace(PracticeUtil::getMessage("party.invite.expired-sender"), ["%player%" => $this->invited]);

        $invitedMsg = PracticeUtil::str_replace(PracticeUtil::getMessage("party.invite.expired-no-time"), ["%player%" => $this->sender]);

        if($this->isSenderOnline())
            PracticeCore::getPlayerHandler()->getPlayer($this->sender)->sendMessage($senderMsg);

        if($this->isInvitedOnline())
            PracticeCore::getPlayerHandler()->getPlayer($this->invited)->sendMessage($invitedMsg);
    }

    private function arePlayersOnline() : bool {
        return $this->isSenderOnline() and $this->isInvitedOnline();
    }

    public function canAcceptRequest(string $player) : bool {

        $result = false;

        if($player === $this->invited and $this->arePlayersOnline()) {

            $p = PracticeCore::getPlayerHandler()->getPlayer($this->sender);

            if($p->isInParty() and PracticeCore::getPartyManager()->isLeaderOFAParty($this->sender))
                $result = true;
        }

        return $result;
    }

    public function isInvitedPlayer(string $player) : bool {
        return $this->invited === $player;
    }

    public function isSameInvite(string $sender, string $invited) : bool {
        return $this->sender === $sender and $this->invited === $invited;
    }

    private function isSenderOnline() : bool {
        return PracticeCore::getPlayerHandler()->isPlayerOnline($this->sender);
    }

    private function isInvitedOnline() : bool {
        return PracticeCore::getPlayerHandler()->isPlayerOnline($this->invited);
    }
}