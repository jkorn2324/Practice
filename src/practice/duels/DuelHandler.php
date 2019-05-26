<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-22
 * Time: 13:33
 */

declare(strict_types=1);

namespace practice\duels;


use practice\arenas\DuelArena;
use practice\duels\groups\DuelGroup;
use practice\duels\groups\MatchedGroup;
use practice\duels\groups\QueuedPlayer;
use practice\PracticeCore;
use practice\PracticeUtil;
use practice\scoreboard\ScoreboardUtil;

class DuelHandler
{
    
    private $queuedPlayers;

    private $matchedGroups;

    /* @var DuelGroup[] */
    private $duels;

    public function __construct() {
        $this->queuedPlayers = [];
        $this->matchedGroups = [];
        $this->duels = [];
    }
    
    // ------------------------------ QUEUE FUNCTIONS --------------------------------

    public function addPlayerToQueue($player, string $queue, bool $isRanked = false) {

        if(PracticeCore::getPlayerHandler()->isPlayerOnline($player)) {

            $p = PracticeCore::getPlayerHandler()->getPlayer($player);

            $name = $p->getPlayerName();

            $peOnly = PracticeCore::getPlayerHandler()->canQueuePEOnly($name);

            $newQueue = new QueuedPlayer($name, $queue, $isRanked, $peOnly);

            $ranked = ($isRanked ? "Ranked" : "Unranked");
            $arr = ["%ranked%" => $ranked, "%queue%" => $queue];

            $msg = PracticeUtil::str_replace(PracticeUtil::getMessage("duels.queue.enter"), $arr);

            $p->sendMessage($msg);

            PracticeCore::getItemHandler()->spawnQueueItems($p->getPlayer());

            if($this->isPlayerInQueue($p->getPlayerName()))
                unset($this->queuedPlayers[$p->getPlayerName()]);

            $this->queuedPlayers[$p->getPlayerName()] = $newQueue;

            ScoreboardUtil::updateSpawnScoreboards();

            //$this->updateDuels();
        }
    }

    public function removePlayerFromQueue($player, bool $sendMsg = false) : void {

        if($this->isPlayerInQueue($player)) {

            $queue = $this->getQueuedPlayer($player);

            if($queue instanceof QueuedPlayer) {

                $ranked = ($queue->isRanked() ? "Ranked" : "Unranked");
                $arr = ["%ranked%" => $ranked, "%queue%" => $queue->getQueue()];

                $msg = PracticeUtil::getMessage("duels.queue.leave");
                $msg = PracticeUtil::str_replace($msg, $arr);

                if ($queue->isPlayerOnline() and $sendMsg) {
                    $p = $queue->getPlayer();
                    $p->sendMessage($msg);
                    PracticeCore::getItemHandler()->spawnHubItems($p, true);
                }
            }

            unset($this->queuedPlayers[$queue->getPlayerName()]);

            //var_dump($this->queuedPlayers);
        }
    }

    public function isPlayerInQueue($player) : bool {

        $name = PracticeUtil::getPlayerName($player);

        $result = false;

        if(!is_null($name))
            $result = array_key_exists($name, $this->queuedPlayers);

        return $result;
    }


    /**
     * @param $player
     * @return QueuedPlayer|null
     */
    public function getQueuedPlayer($player) {
        $name = PracticeUtil::getPlayerName($player);
        $result = null;
        if($this->isPlayerInQueue($player))
            $result = $this->queuedPlayers[$name];
        return $result;
    }

    public function updateQueues() : bool {

        $result = false;

        $keys = array_keys($this->queuedPlayers);

        foreach($keys as $key) {

            if(isset($this->queuedPlayers[$key])) {

                $player = $this->queuedPlayers[$key];

                $remove = false;

                if($player instanceof QueuedPlayer) {

                    if ($player->isPlayerOnline()) {

                        $p = $player->getPlayer();

                        if ($p->isInArena()) {

                            $ranked = ($player->isRanked ? "Ranked" : "Unranked");
                            $arr = ["%ranked%" => $ranked, "%queue%" => $player->getQueue()];

                            $msg = PracticeUtil::getMessage("duels.queue.leave");
                            $msg = PracticeUtil::str_replace($msg, $arr);

                            $p->sendMessage($msg);

                            $remove = true;
                        }
                    } else {
                        $remove = true;
                    }

                    if ($remove === true) {
                        $result = true;
                        unset($this->queuedPlayers[$key]);
                    }
                }
            }

        }

        return $result;
    }

    public function getNumQueuedFor(string $queue, bool $ranked) : int {

        $result = 0;

        foreach($this->queuedPlayers as $aQueue) {

            if($aQueue instanceof QueuedPlayer) {

                if($aQueue->getQueue() === $queue and $ranked === $aQueue->isRanked())
                    $result++;
            }
        }

        return $result;
    }


    public function getNumberOfQueuedPlayers() : int {
        return count($this->queuedPlayers);
    }

    public function getQueuedPlayers() : array {
        return $this->queuedPlayers;
    }

    // ------------------------------ MATCHED PLAYER FUNCTIONS --------------------------------

    public function setPlayersMatched($player, $opponent, bool $isDirect = false, string $queue = null) : void {

        if(!$isDirect) {

            if($this->isPlayerInQueue($player) and $this->isPlayerInQueue($opponent)) {

                $pQueue = $this->getQueuedPlayer($player);
                $oQueue = $this->getQueuedPlayer($opponent);

                if($pQueue instanceof QueuedPlayer and $oQueue instanceof QueuedPlayer) {

                    $ranked = $pQueue->isRanked();
                    $queue = $pQueue->getQueue();

                    $str = ($ranked ? "Ranked" : "Unranked");

                    $oppElo = PracticeCore::getPlayerHandler()->getEloFrom($pQueue->getPlayerName(), $queue);
                    $pElo = PracticeCore::getPlayerHandler()->getEloFrom($oQueue->getPlayerName(), $queue);

                    $msg = PracticeUtil::getMessage("duels.queue.found-match");
                    $msg = PracticeUtil::str_replace($msg, ["%ranked%" => $str, "%queue%" => $queue]);
                    $oppMsg = PracticeUtil::str_replace($msg, ["%elo%" => (($ranked) ? "$oppElo" : ""), "%player%" => $pQueue->getPlayerName()]);
                    $pMsg = PracticeUtil::str_replace($msg, ["%elo%" => (($ranked) ? "$pElo" : ""), "%player%" => $oQueue->getPlayerName()]);

                    $pQueue->getPlayer()->sendMessage($pMsg);
                    $oQueue->getPlayer()->sendMessage($oppMsg);

                    $group = new MatchedGroup($player, $opponent, $queue, $ranked);
                    $this->matchedGroups[] = $group;

                }

                unset($this->queuedPlayers[$pQueue->getPlayerName()], $this->queuedPlayers[$oQueue->getPlayerName()]);
            }
        } else {

            if(!is_null($queue)) {

                if ($this->isPlayerInQueue($player)) $this->removePlayerFromQueue($player, true);
                if ($this->isPlayerInQueue($opponent)) $this->removePlayerFromQueue($opponent, true);

                $group = new MatchedGroup($player, $opponent, $queue, false);
                $this->matchedGroups[] = $group;
            }
        }
    }

    /**
     * @param string $queue
     * @return array|DuelArena[]
     */
    public function getOpenArenas(string $queue) : array {

        $result = [];

        $arenas = PracticeCore::getArenaHandler()->getDuelArenas();

        foreach($arenas as $arena) {
            if($arena instanceof DuelArena) {
                $closed = PracticeCore::getArenaHandler()->isArenaClosed($arena->getName());
                if($closed === false) {
                    $hasKit = $arena->hasKit($queue);
                    if($hasKit === true) $result[] = $arena;
                }
            }
        }
        return $result;
    }

    public function isAnArenaOpen(string $queue) : bool {
        return count($this->getOpenArenas($queue)) > 0;
    }

    /**
     * @param string $queue
     * @return mixed|DuelArena|null
     */
    private function findRandomArena(string $queue) {

        $result = null;

        if($this->isAnArenaOpen($queue)) {
            $openArenas = $this->getOpenArenas($queue);
            $count = count($openArenas);
            $rand = rand(0, $count - 1);
            $res = $openArenas[$rand];
            $result = $res;
        }

        return $result;
    }

    public function isWaitingForDuelToStart($player) : bool {
        return !is_null($this->getGroupFrom($player));
    }

    private function getMatchedIndexOf(MatchedGroup $group) : int {
        $index = array_search($group, $this->matchedGroups);
        if(is_bool($index) and $index === false)
            $index = -1;

        return $index;
    }

    private function isValidMatched(MatchedGroup $group) : bool {
        return $this->getMatchedIndexOf($group) !== -1;
    }

    public function getGroupFrom($player) {
        $str = PracticeUtil::getPlayerName($player);
        $result = null;
        if(!is_null($str)) {
            foreach($this->matchedGroups as $group) {
                if($group instanceof MatchedGroup) {
                    if($group->getPlayerName() === $str or $group->getOpponentName() === $str) {
                        $result = $group;
                        break;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @param $player
     * @return QueuedPlayer|null
     */
    private function findQueueMatch($player) {

        $opponent = null;

        if(isset($player) and $this->isPlayerInQueue($player)) {

            $pQueue = $this->getQueuedPlayer($player);

            $checkForPEQueue = $pQueue->isPEOnly();

            if($pQueue instanceof QueuedPlayer) {

                foreach ($this->queuedPlayers as $queue) {

                    if ($queue instanceof QueuedPlayer) {

                        $equals = $queue->equals($pQueue);

                        if($equals !== true) {

                            if($pQueue->hasSameQueue($queue)) {

                                $found = false;

                                if($checkForPEQueue === true) {

                                    if($queue->getPlayer()->peOnlyQueue()) $found = true;

                                } else {

                                    if($queue->isPEOnly()) {

                                        $found = $pQueue->getPlayer()->peOnlyQueue();

                                    } else $found = true;
                                }

                                if($found === true) {
                                    $opponent = $queue;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $opponent;
    }

    public function didFindMatch($player) : bool {
        return !is_null($this->findQueueMatch($player));
    }

    /**
     * @param $player
     * @return \pocketmine\Player|null
     */
    public function getMatchedPlayer($player) {
        $opponent = null;

        if($this->didFindMatch($player)) {

            $otherQueue = $this->findQueueMatch($player);

            if($otherQueue instanceof QueuedPlayer) {
                if($otherQueue->isPlayerOnline()){
                    $opponent = $otherQueue->getPlayer()->getPlayer();
                }
            }
        }
        return $opponent;
    }

    public function getAwaitingGroups() : array {
        return $this->matchedGroups;
    }

    // ------------------------------ DUEL PLAYER FUNCTIONS --------------------------------

    public function startDuel(MatchedGroup $group) : void {

        $arena = $this->findRandomArena($group->getQueue());

        if(!is_null($arena) and $this->isValidMatched($group)) {

            $index = $this->getMatchedIndexOf($group);

            if($group->isPlayerOnline() and $group->isOpponentOnline()) {
                $duel = new DuelGroup($group, $arena->getName());
                //PracticeCore::getArenaHandler()->setArenaClosed($arena);
                $this->duels[] = $duel;
            }

            unset($this->matchedGroups[$index]);
            $this->matchedGroups = array_values($this->matchedGroups);
        }
    }

    private function getDuelIndexOf(DuelGroup $group) : int {

        $index = array_search($group, $this->duels);
        if(is_bool($index) and $index === false)
            $index = -1;
        return $index;
    }

    private function isValidDuel(DuelGroup $group) : bool {
        return $this->getDuelIndexOf($group) !== -1;
    }

    /**
     * @param $object
     * @param bool $isArena
     * @return DuelGroup|null
     */
    public function getDuel($object, bool $isArena = false) {

        $result = null;

        if(isset($object) and !is_null($object)) {
            if($isArena === false) {
                if (PracticeCore::getPlayerHandler()->isPlayer($object)) {
                    $p = PracticeCore::getPlayerHandler()->getPlayer($object);
                    foreach ($this->duels as $duel) {
                        if ($duel instanceof DuelGroup) {
                            if ($duel->isPlayer($p->getPlayer()) or $duel->isOpponent($p->getPlayer())) {
                                $result = $duel;
                                break;
                            }
                        }
                    }
                }
            } else {
                if (is_string($object) and PracticeCore::getArenaHandler()->isDuelArena($object)) {
                    $arena = PracticeCore::getArenaHandler()->getDuelArena($object);
                    $name = $arena->getName();
                    foreach ($this->duels as $duel) {
                        $arenaName = $duel->getArenaName();
                        if ($arenaName === $name) {
                            $result = $duel;
                            break;
                        }
                    }
                }
            }
        }
        return $result;
    }

    public function isInDuel($player) : bool {
        return PracticeCore::getPlayerHandler()->isPlayer($player) and !is_null($this->getDuel($player));
    }

    public function isArenaInUse($arena) : bool {
        return !is_null($this->getDuel($arena, true));
    }

    public function endDuel(DuelGroup $group) {

        if($this->isValidDuel($group)) {
            $index = $this->getDuelIndexOf($group);
            unset($this->duels[$index]);
        } else unset($group);

        $this->duels = array_values($this->duels);
    }

    public function getDuelsInProgress() : array {
        return $this->duels;
    }

    public function getNumFightsFor(string $queue, bool $ranked) : int {

        $result = 0;

        foreach($this->duels as $duel) {

            if($duel->getQueue() === $queue and $ranked === $duel->isRanked())
                $result += 2;

        }

        return $result;
    }

    /**
     * @param $spec
     * @return null|DuelGroup
     */
    public function getDuelFromSpec($spec) {
        $result = null;
        if(PracticeCore::getPlayerHandler()->isPlayerOnline($spec)) {
            $player = PracticeCore::getPlayerHandler()->getPlayer($spec);
            foreach($this->duels as $duel) {
                if($duel instanceof DuelGroup) {
                    if($duel->isSpectator($player->getPlayerName())) {
                        $result = $duel;
                        break;
                    }
                }
            }
        }
        return $result;
    }

    public function isASpectator($player) : bool {
        $duel = $this->getDuelFromSpec($player);
        return !is_null($duel);
    }
}