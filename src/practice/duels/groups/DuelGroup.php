<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-22
 * Time: 13:36
 */

declare(strict_types=1);

namespace practice\duels\groups;

use pocketmine\block\Block;
use pocketmine\level\Position;
use pocketmine\utils\TextFormat;
use practice\arenas\DuelArena;
use practice\duels\misc\DuelInvInfo;
use practice\duels\misc\DuelPlayerHit;
use practice\duels\misc\DuelSpectator;
use practice\player\PracticePlayer;
use practice\PracticeCore;
use practice\PracticeUtil;
use practice\scoreboard\Scoreboard;

class DuelGroup
{

    public const NONE = "None";

    private const NO_SPEC_MSG = "spectators.none";

    public const MAX_COUNTDOWN_SEC = 5;

    public const MAX_DURATION_MIN = 30;

    public const MAX_END_DELAY_SEC = 1;

    private $playerName;
    private $opponentName;
    private $arenaName;
    private $winnerName;
    private $loserName;

    private $queue;

    private $origOppTag;
    private $origPlayerTag;

    private $currentTick;
    private $countdownTick;
    private $endTick;

    private $ranked;

    private $started;
    private $ended;

    /* @var DuelSpectator[] */
    private $spectators;

    private $blocks = [];

    /* @var DuelPlayerHit[] */
    private $playerHits;

    /* @var DuelPlayerHit[] */
    private $oppHits;

    private $fightingTick;

    private $arena;

    private $opponentDevice;
    private $playerDevice;

    public function __construct(MatchedGroup $group, string $arena)
    {
        $this->playerName = $group->getPlayerName();
        $this->opponentName = $group->getOpponentName();
        $this->winnerName = self::NONE;
        $this->loserName = self::NONE;
        $this->arenaName = $arena;

        $this->queue = $group->getQueue();
        $this->ranked = $group->isRanked();

        $player = $group->getPlayer();
        $opponent = $group->getOpponent();

        $p = $player->getPlayer();
        $o = $opponent->getPlayer();

        $this->origPlayerTag = $p->getNameTag();
        $this->origOppTag = $o->getNameTag();

        $this->opponentDevice = $opponent->getDevice();
        $this->playerDevice = $player->getDevice();

        $p->setNameTag(TextFormat::RED . $this->playerName);
        $o->setNameTag(TextFormat::RED . $this->opponentName);

        $this->started = false;
        $this->ended = false;

        $this->fightingTick = 0;

        $this->currentTick = 0;
        $this->countdownTick = 0;
        $this->endTick = -1;

        $this->playerHits = [];
        $this->oppHits = [];

        $this->arena = PracticeCore::getArenaHandler()->getDuelArena($arena);

        $this->placeInDuel($player, $opponent);

        $this->spectators = [];
    }

    public function isRanked() : bool {
        return $this->ranked;
    }

    public function isSpleef() : bool {
        return PracticeUtil::equals_string($this->queue, "Spleef", "spleef", "SPLEEF");
    }

    public function getQueue() : string {
        return $this->queue;
    }

    private function placeInDuel(PracticePlayer $p, PracticePlayer $o) : void {
        $p->placeInDuel($this);
        $o->placeInDuel($this);
    }

    public function isOpponent($player): bool
    {
        $result = false;
        if (PracticeCore::getPlayerHandler()->isPlayerOnline($player)) {
            $p = PracticeCore::getPlayerHandler()->getPlayer($player);
            $name = $p->getPlayerName();
            $result = $name === $this->opponentName;
        }
        return $result;
    }

    public function isPlayer($player): bool
    {
        $result = false;

        if (PracticeCore::getPlayerHandler()->isPlayerOnline($player)) {
            $p = PracticeCore::getPlayerHandler()->getPlayer($player);
            $name = $p->getPlayerName();
            $result = $name === $this->playerName;
        }

        return $result;
    }

    /**
     * @return \practice\player\PracticePlayer|null
     */
    public function getPlayer()
    {
        return PracticeCore::getPlayerHandler()->getPlayer($this->playerName);
    }

    /**
     * @return \practice\player\PracticePlayer|null
     */
    public function getOpponent()
    {
        return PracticeCore::getPlayerHandler()->getPlayer($this->opponentName);
    }

    public function endDuelPrematurely(bool $disablePlugin = false) : void {

        $winner = self::NONE;

        $loser = self::NONE;

        $premature = true;

        if($disablePlugin === true) $this->setDuelEnded();

        if($disablePlugin === false) {

            if ($this->isDuelRunning() or $this->didDuelEnd()) {

                $results = $this->getOfflinePlayers();

                $winner = $results["winner"];
                $loser = $results["loser"];

                $premature = false;
            }
        }

        $this->winnerName = $winner;
        $this->loserName = $loser;

        $this->endDuel($premature);
    }

    public function setResults(string $winner = self::NONE, string $loser = self::NONE) {

        $this->winnerName = $winner;
        $this->loserName = $loser;

        if($winner !== self::NONE and $loser !== self::NONE) {

            if($this->arePlayersOnline()){

                $p = $this->getPlayer();
                $playerDuelInfo = new DuelInvInfo($p->getPlayer(), $this->queue, count($this->playerHits));

                $o = $this->getOpponent();
                $oppDuelInfo = new DuelInvInfo($o->getPlayer(), $this->queue, count($this->oppHits));

                $p->addToDuelHistory($playerDuelInfo, $oppDuelInfo);

                $o->addToDuelHistory($oppDuelInfo, $playerDuelInfo);
            }
        }
        $this->setDuelEnded();
    }

    public function update(): void {

        if (!$this->arePlayersOnline()) {
            $this->endDuelPrematurely();
            return;
        }

        if ($this->isLoadingDuel()) {

            if (!PracticeCore::getArenaHandler()->isArenaClosed($this->arenaName))
                PracticeCore::getArenaHandler()->setArenaClosed($this->arenaName);

            $this->countdownTick++;

            $p = $this->getPlayer();
            $o = $this->getOpponent();

            if($this->countdownTick === 5) {
                $p->setScoreboard(Scoreboard::DUEL_SCOREBOARD);
                $o->setScoreboard(Scoreboard::DUEL_SCOREBOARD);
            } else {
                if($this->countdownTick % 2 === 0) {
                    $p->updateScoreboard("opponent", ["%player%" => $o->getPlayerName()]);
                    $o->updateScoreboard("opponent", ["%player%" => $p->getPlayerName()]);
                }
            }

            $max = PracticeUtil::secondsToTicks(self::MAX_COUNTDOWN_SEC);

            if ($this->countdownTick % 20 === 0 and $this->countdownTick !== 0) {

                $second = self::MAX_COUNTDOWN_SEC - PracticeUtil::ticksToSeconds($this->countdownTick);
                $msg = null;

                if ($second != 0) {
                    $msg = PracticeUtil::str_replace(PracticeUtil::getMessage("duels.start.countdown"), ["%seconds%" => "$second"]);
                } else {
                    $ranked = ($this->ranked ? "Ranked" : "Unranked");
                    $msg = PracticeUtil::str_replace(PracticeUtil::getMessage("duels.start.go-msg"), ["%queue%" => $this->queue, "%ranked%" => $ranked]);
                }

                if (!is_null($msg)) {
                    $this->broadcastMsg($msg);
                }
            }

            if ($this->countdownTick >= $max) $this->start();

        } elseif ($this->isDuelRunning()) {

            $duration = $this->getDuration();
            $maxDuration = PracticeUtil::minutesToTicks(self::MAX_DURATION_MIN);

            if($this->fightingTick > 0) {
                $this->fightingTick--;
                if($this->fightingTick <= 0) {
                    $this->fightingTick = 0;
                }
            }

            if($duration % 20 === 0)
                $this->updateScoreboards();

            $centerY = $this->getArena()->getSpawnPosition()->y;

            $playerY = $this->getPlayer()->getPlayer()->getPosition()->y;

            $oppY = $this->getOpponent()->getPlayer()->getPosition()->y;

            if($playerY + 2.5 <= $centerY) {
                $this->setResults($this->opponentName, $this->playerName);
                $this->endDuel();
                return;
            }

            if($oppY + 2.5 <= $centerY) {
                $this->setResults($this->playerName, $this->opponentName);
                $this->endDuel();
                return;
            }

            if($duration >= $maxDuration) {
                $this->setResults();
                $this->endDuel();
                return;
            }
        } else {
            $difference = $this->currentTick - $this->endTick;
            $seconds = PracticeUtil::ticksToSeconds($difference);
            if($seconds >= self::MAX_END_DELAY_SEC) {
                $this->endDuel();
            }
        }

        $this->currentTick++;
    }

    private function updateScoreboards() : void {

        $duration = $this->getDurationString();

        if($this->isPlayerOnline()) {
            $p = $this->getPlayer();
            $p->updateScoreboard('duration', ['%time%' => $duration]);
        }

        if($this->isOpponentOnline()) {
            $o = $this->getOpponent();
            $o->updateScoreboard('duration', ['%time%' => $duration]);
        }

        foreach ($this->spectators as $spectator) {

            $spectator->update();

            /*if(PracticeCore::getPlayerHandler()->isPlayerOnline($spectator)) {
                $pl = PracticeCore::getPlayerHandler()->getPlayer($spectator);
                $pl->updateScoreboard();
            }*/
        }
    }

    public function isDuelRunning(): bool
    {
        return $this->started === true and $this->ended === false;
    }

    public function isLoadingDuel(): bool
    {
        return $this->started === false and $this->ended === false;
    }

    public function didDuelEnd(): bool
    {
        return $this->started === true and $this->ended === true;
    }

    private function endPremature(): bool
    {
        return !$this->started and $this->ended;
    }

    public function arePlayersOnline(): bool
    {
        $result = false;
        if (PracticeCore::getPlayerHandler()->isPlayer($this->opponentName) and PracticeCore::getPlayerHandler()->isPlayer($this->playerName)) {
            $opp = $this->getOpponent();
            $pl = $this->getPlayer();
            $result = $opp->isOnline() and $pl->isOnline();
        }
        return $result;
    }

    private function getOfflinePlayers(): array {

        $result = ["winner" => self::NONE, "loser" => self::NONE];

        if (!$this->arePlayersOnline()) {

            if (!is_null($this->getPlayer()) and $this->getPlayer()->isOnline()) {
                $result["winner"] = $this->playerName;
                $result["loser"] = $this->opponentName;
            } elseif (!is_null($this->getOpponent()) and $this->getOpponent()->isOnline()) {
                $result["winner"] = $this->opponentName;
                $result["loser"] = $this->playerName;
            }
        }
        return $result;
    }

    private function start() {

        $this->started = true;

        if($this->arePlayersOnline()) {

            $p = $this->getPlayer();
            $o = $this->getOpponent();

            PracticeUtil::setFrozen($p->getPlayer(), false, true);
            PracticeUtil::setFrozen($o->getPlayer(), false, true);
        }
    }

    private function endDuel(bool $endPrematurely = false) : void {

        //$this->updateBlocks();

        $this->clearBlocks();

        $messageList = $this->getFinalMessage($endPrematurely);

        $messageList = PracticeUtil::arr_replace_values($messageList, ["*" => PracticeUtil::getLineSeparator($messageList)]);

        $sizeMsgList = count($messageList);

        for($i = 0; $i < $sizeMsgList; $i++) {
            $msg = strval($messageList[$i]);
            $this->broadcastMsg($msg, true);
        }

        if($this->isPlayerOnline()) {
            $p = $this->getPlayer();
            if($p->getPlayer()->isAlive()) {
                PracticeUtil::resetPlayer($p->getPlayer());
            }
            $p->getPlayer()->setNameTag($this->origPlayerTag);
        }

        if($this->isOpponentOnline()) {
            $p = $this->getOpponent();
            if($p->getPlayer()->isAlive()) {
                PracticeUtil::resetPlayer($p->getPlayer());
            }
            $p->getPlayer()->setNameTag($this->origOppTag);
        }

        foreach($this->spectators as $spectator) {
            $spectator->resetPlayer();
        }

        $this->spectators = [];

        PracticeCore::getArenaHandler()->setArenaOpen($this->arenaName);

        PracticeCore::getDuelHandler()->endDuel($this);
    }

    private function getFinalMessage(bool $endPrematurely) : array {

        $winnerChangedElo = 0;
        $loserChangedElo = 0;

        if($endPrematurely === false) {

            if($this->ranked === true) {

                $winnerDevice = $this->isOpponent($this->winnerName) ? $this->opponentDevice : $this->playerDevice;

                $loserDevice = $this->isOpponent($this->loserName) ? $this->opponentDevice : $this->playerDevice;

                $elo = PracticeCore::getPlayerHandler()->setEloOf($this->winnerName, $this->loserName, $this->queue, $winnerDevice, $loserDevice);

                $winnerChangedElo = $elo['winner'];
                $loserChangedElo = $elo['loser'];
            }
        }

        $result = [];

        if($endPrematurely) {
            $result = ["*", $this->getResultMessage(), "*"];
        } else {
            if($this->ranked) {
                $result = ["*", $this->getResultMessage(), "*", $this->getEloChanges($winnerChangedElo, $loserChangedElo), "*"];
            } else {
                $result = ["*", $this->getResultMessage(), "*"];
            }

            $size = count($this->spectators);
            if(strlen($this->getSpectatorMessage()) > 0 and $size > 0 and $this->getSpectatorMessage() !== self::NO_SPEC_MSG) {
                $result += [$this->getSpectatorMessage(), "*"];
            }
        }
        return $result;
    }

    private function getSpectatorMessage() : string {

        $result = "";

        $msg = PracticeUtil::getMessage("duels.spectator.end-msg");

        $replaced = "";

        if(count($this->spectators) > 0) {
            $size = count($this->spectators);
            $len = count($this->spectators);
            $left = "(+%left% more)";
            if($len > 4) {
                $len = 4;
                $others = $size - $len;
                $left = PracticeUtil::str_replace($left, ["%left%" => "$others"]);
            } else {
                $left = null;
            }

            $count = 0;
            $len = $len - 1;

            foreach($this->spectators as $s) {
                $spec = $s->getPlayerName();
                if($count < $len) {
                    $comma = ($count === $len ? "" : ", ");
                    $replaced = $replaced . ($spec . $comma);
                } else break;

                $count++;
            }

            if(!is_null($left)) {
                $replaced = $replaced . " $left";
            }

            $result = PracticeUtil::str_replace($msg, ["%spec%" => $replaced, "%num%" => "$size"]);
        } else {
            $result = self::NO_SPEC_MSG;
        }

        return $result;
    }

    private function getResultMessage() : string {
        $result = PracticeUtil::str_replace(PracticeUtil::getMessage("duels.end.result-msg"), ["%winner%" => $this->winnerName, "%loser%" => $this->loserName]);
        if($this->winnerName === self::NONE and $this->loserName === self::NONE) {
            $result = PracticeUtil::str_replace($result, ["[%wElo%]" => "", "[%lElo%]" => ""]);
        } else {
            $wElo = PracticeCore::getPlayerHandler()->getEloFrom($this->winnerName, $this->queue);
            $lElo = PracticeCore::getPlayerHandler()->getEloFrom($this->loserName, $this->queue);
            $result = PracticeUtil::str_replace($result, ["%wElo%" => "$wElo", "%lElo%" => "$lElo"]);
        }
        return $result;
    }

    private function getEloChanges(int $winner, int $loser) : string {
        $result = PracticeUtil::getMessage("duels.end.elo-changes");
        $wElo = PracticeCore::getPlayerHandler()->getEloFrom($this->winnerName, $this->queue);
        $lElo = PracticeCore::getPlayerHandler()->getEloFrom($this->loserName, $this->queue);
        $result = PracticeUtil::str_replace($result, ["%winner%" => $this->winnerName, "%loser%" => $this->loserName, "%newWElo%" => "$wElo", "%newLElo%" => $lElo]);
        return PracticeUtil::str_replace($result, ["%wElo%" => "$winner", "%lElo%" => "$loser"]);
    }


    public function getDuration(): int {
        $duration = $this->currentTick - $this->countdownTick;
        if ($this->didDuelEnd()) {
            $endTickDiff = $this->currentTick - $this->endTick;
            $duration = $duration - $endTickDiff;
        }
        return $duration;
    }

    private function broadcastMsg(string $msg, bool $sendSpecs = false, $player = null): void {

        $oppMsg = $msg;
        $pMsg = $msg;

        if($this->isOpponentOnline()) {
            $o = $this->getOpponent();
            if(PracticeUtil::isLineSeparator($oppMsg)) {
                if($o->getDevice() === PracticeUtil::WINDOWS_10) $oppMsg .= PracticeUtil::WIN10_ADDED_SEPARATOR;
            }
            $o->sendMessage($oppMsg);
        }

        if($this->isPlayerOnline()) {
            $p = $this->getPlayer();
            if(PracticeUtil::isLineSeparator($pMsg)) {
                if($p->getDevice() === PracticeUtil::WINDOWS_10) $pMsg .= PracticeUtil::WIN10_ADDED_SEPARATOR;
            }
            $p->sendMessage($pMsg);
        }

        if($sendSpecs === true) {

            $spectators = $this->getSpectators();

            foreach($spectators as $spec) {

                $exec = true;

                if(!is_null($player) and PracticeCore::getPlayerHandler()->isPlayerOnline($player)) {
                    $p = PracticeCore::getPlayerHandler()->getPlayer($player);
                    if($spec->isOnline()) {
                        $spectator = $spec->getPlayer();
                        if($spectator->equals($p)) {
                            $exec = false;
                        }
                    }
                } else {
                    if(!$spec->isOnline()) $exec = false;
                }

                if($exec === true) {
                    if($spec->isOnline()) {
                        $specMsg = $msg;
                        $pl = $spec->getPlayer();
                        if($pl->getDevice() === PracticeUtil::WINDOWS_10 and PracticeUtil::isLineSeparator($specMsg))
                            $specMsg .= PracticeUtil::WIN10_ADDED_SEPARATOR;
                        $pl->sendMessage($specMsg);
                    }
                }
            }
        }
    }

    public function addHitFrom($player) {

        if($this->isPlayer($player)) {

            $hit = new DuelPlayerHit($this->opponentName, $this->currentTick);
            $add = true;

            $size = count($this->oppHits) - 1;

            for($i = $size; $i > -1; $i--) {
                $pastHit = $this->oppHits[$i];
                if($pastHit->equals($hit)) {
                    $add = false;
                    break;
                }
            }


            if($add === true) $this->oppHits[] = $hit;

        } elseif ($this->isOpponent($player)) {

            $hit = new DuelPlayerHit($this->playerName, $this->currentTick);
            $add = true;

            $size = count($this->playerHits) - 1;

            for($i = $size; $i > -1; $i--) {
                $pastHit = $this->playerHits[$i];
                if($pastHit->equals($hit)) {
                    $add = false;
                    break;
                }
            }

            if($add === true) $this->playerHits[] = $hit;
        }
    }

    public function isInDuelCombat() : bool {
        return $this->fightingTick > 0;
    }

    public function setInDuelCombat() : void {
        $this->fightingTick = PracticeUtil::secondsToTicks(3);
    }

    public function getFightingTick() : int {
        return $this->fightingTick;
    }

    private function setDuelEnded(bool $result = true) {
        $this->ended = $result;
        $this->endTick = $this->endTick == -1 ? $this->currentTick : $this->endTick;
    }

    public function getArenaName() : string {
        return $this->arenaName;
    }

    /**
     * @return DuelArena|null
     */
    public function getArena() {
        return $this->arena;
    }

    public function canBuild() : bool {
        return $this->getArena()->canBuild();
    }

    public function canBreak() : bool {
        $result = $this->canBuild();
        return ($this->isSpleef()) ? true : $result;
    }

    public function isPlacedBlock($block) {
        return $this->indexOfBlock($block) !== -1;
    }

    private function indexOfBlock($block) : int {

        $index = -1;

        if($block instanceof Block) {
            $vec = $block->asPosition();
            if(is_null($vec->level)) $vec->level = $this->getArena()->getLevel();
            $index = array_search($vec, $this->blocks);
            if(is_bool($index) and $index === false) {
                $index = -1;
            }
        }

        //echo ("$index is an index!\n");

        return $index;
    }

    private function clearBlocks() : void {

        $level = $this->getArena()->getLevel();

        $size = count($this->blocks);

        for($i = 0; $i < $size; $i++) {
            $block = $this->blocks[$i];
            if($block instanceof Position) {
                $spleef = $this->isSpleef();
                $replacedBlock = ($spleef === true) ? Block::get(Block::SNOW_BLOCK) : Block::get(0);
                $level->setBlock($block, $replacedBlock);
            }
        }

        $this->blocks = [];
    }


    public function addBlock(Block $position) : void {
        $pos = $position->asPosition();
        $this->blocks[] = $pos;
    }

    public function removeBlock(Block $position) : bool {

        $result = false;

        if($this->isSpleef() and $this->isSpleefBlock($position)) {
            $this->addBlock($position);
            $result = true;
        } else {
            if ($this->isPlacedBlock($position)) {
                $result = true;
                $index = $this->indexOfBlock($position);
                unset($this->blocks[$index]);
                $this->blocks = array_values($this->blocks);
            }
        }

        return $result;
    }

    private function isSpleefBlock(Block $position) : bool {
        $id = $position->getId();
        return $id === Block::SNOW_BLOCK or $id === Block::SNOW_LAYER;
    }

    public function addSpectator($spectator) : void {

        if(PracticeCore::getPlayerHandler()->isPlayerOnline($spectator)) {

            $p = PracticeCore::getPlayerHandler()->getPlayer($spectator);

            $pl = $p->getPlayer();

            $spec = new DuelSpectator($pl);

            $center = $this->getArena()->getSpawnPosition();

            $spec->teleport($center);

            $this->spectators[] = $spec;

            $msg = PracticeUtil::getMessage("duels.spectator.join");
            $msg = PracticeUtil::str_replace($msg, ["%spec%" => $spec->getPlayerName()]);
            $this->broadcastMsg($msg, true);
        }
    }

    public function isSpectator($spec) : bool {
        return $this->indexOfSpec($spec) !== -1;
    }

    public function removeSpectator($spec, bool $msg = false) : void {

        if($this->isSpectator($spec)) {

            $p = PracticeCore::getPlayerHandler()->getPlayer($spec);

            PracticeUtil::resetPlayer($p->getPlayer(), true);

            if($msg === true) {
                $msg = PracticeUtil::str_replace(PracticeUtil::getMessage("duels.spectator.leave"), ["%spec%" => "You", "is" => "are"]);
                $p->sendMessage($msg);
                $broadcastedMsg = PracticeUtil::str_replace(PracticeUtil::getMessage("duels.spectator.leave"), ["%spec%" => $p->getPlayerName()]);
                $this->broadcastMsg($broadcastedMsg, true, $p->getPlayer());
            }

            $index = $this->indexOfSpec($spec);
            unset($this->spectators[$index]);
            $this->spectators = array_values($this->spectators);
        }
    }

    private function indexOfSpec($spectator) : int {

        $result = -1;
        $count = 0;

        $name = null;

        if(!is_null(PracticeUtil::getPlayerName($spectator))) $name = PracticeUtil::getPlayerName($spectator);
        elseif ($spectator instanceof DuelSpectator) $name = $spectator->getPlayerName();

        if(!is_null($name)) {

            foreach($this->spectators as $spec) {
                if($spec->isOnline()) {
                    $specName = $spec->getPlayerName();
                    if($name === $specName) {
                        $result = $count;
                        break;
                    }
                }

                $count++;
            }
        }

        return $result;
    }


    /**
     * @return array|DuelSpectator[]
     */
    private function getSpectators() : array {

        $result = [];

        $specs = count($this->spectators) - 1;

        for($i = $specs; $i > -1; $i--) {
            $spec = $this->spectators[$i];
            if($spec->isOnline()) {
                $result[] = $spec;
            } else {
                unset($this->spectators[$i]);
                $this->spectators = array_values($this->spectators);
            }
        }

        return $result;
    }

    private function isPlayerOnline() : bool {
        return !is_null($this->getPlayer()) and $this->getPlayer()->isOnline();
    }

    private function isOpponentOnline() : bool {
        return !is_null($this->getOpponent()) and $this->getOpponent()->isOnline();
    }

    public function equals($object) : bool {
        $result = false;
        if($object instanceof DuelGroup) {
            if($object->getPlayer() === $this->getPlayer() and $object->getOpponent() === $this->getOpponent()) {
                if($object->getQueue() === $this->getQueue()) {
                    $result = true;
                }
            }
        }
        return $result;
    }

    public function getDurationString() : string {

        $s = "mm:ss";

        $seconds = PracticeUtil::ticksToSeconds($this->getDuration());
        $minutes = PracticeUtil::ticksToMinutes($this->getDuration());

        if($minutes > 0) {
            if($minutes < 10) {
                $s = PracticeUtil::str_replace($s, ["mm" => "0$minutes"]);
            } else {
                $s = PracticeUtil::str_replace($s,  ["mm" => "$minutes"]);
            }
        } else {
            $s = PracticeUtil::str_replace($s,  ["mm" => "00"]);
        }

        $seconds = $seconds % 60;

        if($seconds > 0) {
            if($seconds < 10) {
                $s = PracticeUtil::str_replace($s, ["ss" => "0$seconds"]);
            } else {
                $s = PracticeUtil::str_replace($s, ["ss" => "$seconds"]);
            }
        } else {
            $s = PracticeUtil::str_replace($s, ["ss" => "00"]);
        }

        return $s;
    }
}