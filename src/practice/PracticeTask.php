<?php

declare(strict_types=1);

namespace practice;


use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use practice\duels\groups\DuelGroup;
use practice\duels\groups\QueuedPlayer;
use practice\player\PracticePlayer;
use practice\scoreboard\ScoreboardUtil;

class PracticeTask extends Task {

    /** @var PracticeCore */
    private $core;

    /** @var int */
    private $seconds = 60*60*3;

    /** @var int */
    private $announcementTime = 0;

    /** @var string[] */
    private $announcements;

    private const MAX_ANNOUNCEMENT_TIME = 45;

    /**
     * PracticeTask constructor.
     * @param PracticeCore $core
     */
    public function __construct(PracticeCore $core) {
        $this->core = $core;
        $this->announcements = [
            TextFormat::AQUA . 'See a hacker online? Use ' . TextFormat::YELLOW . '/report hacker' . TextFormat::AQUA .' to notify the staff of hackers on the server.',
            TextFormat::AQUA . 'Find a bug on the server? Use ' . TextFormat::YELLOW . '/report bug' . TextFormat::AQUA . ' to notify the staff of bugs on the server.',
            TextFormat::AQUA . 'Is a staff abusing or doing any other misconduct? Use ' . TextFormat::YELLOW . '/report staff' . TextFormat::AQUA . ' to notify the owner of abusing staff.'
        ];
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick) {
        $this->seconds--;

        $this->broadcastAnnouncement();
        $this->updateDuels();
        $this->updatePlayers();
        $this->checkForReload();

        PracticeCore::getPartyManager()->updateInvites();
        PracticeCore::getPlayerHandler()->updateLeaderboards();
    }

    private function broadcastAnnouncement() : void {
        $server = $this->core->getServer();
        if($this->announcementTime > self::MAX_ANNOUNCEMENT_TIME) {
            $server->broadcastMessage(
                PracticeUtil::getMessage('broadcast-msg') . "\n" . $this->announcements[rand(0, 2)]
            );
            $this->announcementTime = 0;
        }
        $this->announcementTime++;
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

    private function checkForReload(): void {
        $server = $this->core->getServer();
        $message = "[Server] Server restarting in ";

        if($this->seconds < 0) {
            $server->reload();
        } elseif($this->seconds < 10) {
            PracticeUtil::broadcastMsg($message . "$this->seconds seconds.");
        } elseif($this->seconds == 60 or $this->seconds == 60*2 or $this->seconds == 60*5 or
            $this->seconds == 60*10 or $this->seconds == 60*15) {
            PracticeUtil::broadcastMsg($message . $this->seconds / 60 . " minutes.");
        }
    }

}