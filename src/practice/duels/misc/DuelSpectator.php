<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-17
 * Time: 11:27
 */

declare(strict_types=1);

namespace practice\duels\misc;


use pocketmine\level\Position;
use pocketmine\Player;
use practice\PracticeCore;
use practice\PracticeUtil;

class DuelSpectator {

    private $name;

    private $boundingBox;

    public function __construct(Player $player) {

        $this->name = $player->getName();

        $this->boundingBox = $player->getBoundingBox();

        $player->boundingBox->contract($player->width, 0, $player->height);

        PracticeUtil::setInSpectatorMode($player, true, true);

        /*if(PracticeCore::getPlayerHandler()->isPlayerOnline($player)) {
            $pl = PracticeCore::getPlayerHandler()->getPlayer($player);
            //$pl->setScoreboard(Scoreboard::SPEC_SCOREBOARD);
        }*/

        PracticeCore::getItemHandler()->spawnSpecItems($player);
    }

    public function teleport(Position $pos) : void {

        if($this->isOnline()) {
            $p = $this->getPlayer()->getPlayer();
            $pl = $p->getPlayer();
            $pl->teleport($pos);
        }
    }

    public function resetPlayer(bool $disablePlugin = false) : void {

        if($this->isOnline()) {

            $p = $this->getPlayer()->getPlayer();

            $p->boundingBox = $this->boundingBox;

            PracticeUtil::resetPlayer($p, true, true, $disablePlugin);
        }
    }

    public function sendMessage(string $msg) : void {
        if($this->isOnline()) {
            $this->getPlayer()->sendMessage($msg);
        }
    }

    public function update(string $duration) : void {
        if($this->isOnline()) {
            $p = $this->getPlayer();
            $p->updateLineOfScoreboard(2, ' ' . $duration);
        }
    }

    public function getPlayer() {
        return PracticeCore::getPlayerHandler()->getPlayer($this->name);
    }

    public function isOnline() : bool {
        $p = $this->getPlayer();
        return !is_null($p) and $p->isOnline();
    }

    public function getPlayerName() : string {
        return $this->name;
    }

}