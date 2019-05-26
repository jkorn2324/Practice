<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-18
 * Time: 15:34
 */

declare(strict_types=1);

namespace practice;


use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use practice\duels\groups\DuelGroup;
use practice\duels\groups\MatchedGroup;
use practice\duels\groups\QueuedPlayer;
use practice\player\PracticePlayer;
use practice\scoreboard\ScoreboardUtil;

class PracticeTask extends Task
{
    private $core;

    private $currentTick;

    private $ticksBetweenReload;

    private $randomAnnouncement;

    /** @var int */
    private $announcementTime = 0;

    private $maxAnnouncementTime;

    public function __construct(PracticeCore $c) {

        $this->core = $c;
        $this->currentTick = 0;
        $this->maxAnnouncementTime = PracticeUtil::minutesToTicks(2);
        //$this->ticksBetweenReload = PracticeUtil::minutesToTicks(1);
        $this->ticksBetweenReload = PracticeUtil::hoursToTicks(3);
        $this->randomAnnouncement = [
            TextFormat::AQUA . 'See a hacker online? Use ' . TextFormat::YELLOW . '/report hacker' . TextFormat::AQUA .' to notify the staff of hackers on the server.',
            TextFormat::AQUA . 'Find a bug on the server? Use ' . TextFormat::YELLOW . '/report bug' . TextFormat::AQUA . ' to notify the staff of bugs on the server.',
            TextFormat::AQUA . 'Is a staff abusing or doing any other misconduct? Use ' . TextFormat::YELLOW . '/report staff' . TextFormat::AQUA . ' to notify the owner of abusing staff.'
        ];
    }

    public function onRun(int $tick) {

        $this->updateWorlds();
        $this->updateDuels();
        $this->updatePlayers();
        $this->updateParties();
        $this->checkForReload();

        $minutes = PracticeUtil::ticksToMinutes($this->currentTick);

        if($minutes % 10 === 0 and $minutes !== 0 and $this->isExactMin($this->currentTick))
            $this->updateLeaderboards();

        $this->currentTick++;
    }

    private function updateWorlds() : void {

        $this->announcementTime++;

        $server = $this->core->getServer();

        if($this->announcementTime > $this->maxAnnouncementTime) {
            $server->broadcastMessage(
                PracticeUtil::getMessage('broadcast-msg') . "\n" . $this->randomAnnouncement[rand(0, 2)]
            );
            $this->announcementTime = 0;
        }
    }

    private function updatePlayers() : void {

        $playerHandler = PracticeCore::getPlayerHandler();

        $duelHandler = PracticeCore::getDuelHandler();

        $array = $playerHandler->getOnlinePlayers();

        $size = count($array);
        
        for($i = 0; $i < $size; $i++) {

            if(isset($array[$i])) {

                $player = $array[$i];

                if ($player instanceof PracticePlayer)

                    $player->updatePlayer();

            }
        }

        if($duelHandler->updateQueues()) ScoreboardUtil::updateSpawnScoreboards("in-queues");
    }

    private function updateDuels() : void {

        $duelHandler = PracticeCore::getDuelHandler();

        PracticeCore::get1vs1Handler()->update();

        $queuedPlayers = $duelHandler->getQueuedPlayers();

        $awaitingMatches = $duelHandler->getAwaitingGroups();

        $duels = $duelHandler->getDuelsInProgress();

        $keys = array_keys($queuedPlayers);

        foreach($keys as $key) {

            if(isset($queuedPlayers[$key])) {

                $queue = $queuedPlayers[$key];

                if ($queue instanceof QueuedPlayer) {
                    $name = $queue->getPlayerName();

                    if ($queue->isPlayerOnline()) {
                        if ($duelHandler->didFindMatch($name)) {
                            $opponent = $duelHandler->getMatchedPlayer($name);
                            $duelHandler->setPlayersMatched($name, $opponent);
                        }
                    }
                }
            }
        }

        foreach($awaitingMatches as $match) {

            $queue = $match->getQueue();

            if($duelHandler->isAnArenaOpen($queue))
                $duelHandler->startDuel($match);
        }

        foreach($duels as $duel) {

            if($duel instanceof DuelGroup) $duel->update();

        }
    }

    private function updateParties() : void {
        PracticeCore::getPartyManager()->updateInvites();
    }

    private function updateLeaderboards() : void {
        PracticeCore::getPlayerHandler()->updateLeaderboards();
    }

    private function checkForReload() : void {

        $ticksLeft = $this->ticksBetweenReload - $this->currentTick;

        $hours = abs(PracticeUtil::ticksToHours($ticksLeft));

        $minutes = abs(PracticeUtil::ticksToMinutes($ticksLeft));

        $seconds = abs(PracticeUtil::ticksToSeconds($ticksLeft));

        if ($this->isExactHr($ticksLeft)) {
            if($hours === 2 or $hours === 1) {
                $msg = '[Server] ' . $hours . ' hour(s) until server restart.';
                PracticeUtil::broadcastMsg($msg);
            }
        } elseif($this->isExactMin($ticksLeft)) {

            $broadcast = false;

            if($minutes === 150 or $minutes === 90) {
                $hrs = intval($minutes / 60);
                $mins = $minutes % 60;
                $msg = '[Server] ' . $hrs . ' hour(s) and ' . $mins . ' minutes until server restart.';
                $broadcast = true;
            } elseif($minutes === 30 or $minutes === 10 or $minutes === 5 or $minutes === 1) {
                $msg = '[Server] ' . $minutes . ' minute(s) until server restart.';
                $broadcast = true;
            }

            if($broadcast === true) PracticeUtil::broadcastMsg($msg);

        } elseif ($this->isExactSec($ticksLeft)) {
            if($seconds === 30 or $seconds <= 10) {
                $msg = '[Server] ' . $seconds . ' seconds until server restart.';
                if($seconds === 10)
                    $msg = '[Server] Restarting in ' . $seconds . '...';
                elseif ($seconds < 10 and $seconds > 0)
                    $msg = '[Server] ' . $seconds . '...';
                PracticeUtil::broadcastMsg($msg);
            }
        }

        if($this->currentTick > $this->ticksBetweenReload)
            $this->core->getServer()->reload();
    }

    private function isExactMin(int $tick) : bool {
        return ($tick % 1200) === 0;
    }

    private function isExactHr(int $tick) : bool {
        return ($tick % 72000) === 0;
    }

    private function isExactSec(int $tick) : bool {
        return ($tick % 20) === 0;
    }
}