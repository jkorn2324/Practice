<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-18
 * Time: 09:20
 */

declare(strict_types=1);

namespace practice\player;


use jojoe77777\FormAPI\SimpleForm;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\form\Form;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use practice\arenas\FFAArena;
use practice\arenas\PracticeArena;
use practice\duels\groups\DuelGroup;
use practice\duels\misc\DuelInvInfo;
use practice\game\entity\FishingHook;
use practice\game\FormUtil;
use practice\player\disguise\DisguiseInfo;
use practice\PracticeCore;
use practice\PracticeUtil;
use practice\scoreboard\Scoreboard;

class PracticePlayer
{
    public const MAX_COMBAT_TICKS = 10;
    public const MAX_ENDERPEARL_SECONDS = 15;

    //BOOLEANS
    private $inCombat;
    private $canThrowPearl;
    private $hasKit;
    private $antiSpam;
    private $canHitPlayer;
    private $isLookingAtForm;
    private $invId;

    //STRINGS
    private $playerName;
    private $currentName;
    private $currentArena;

    //INTEGERS
    private $currentSec;
    private $antiSendSecs;
    private $lastSecHit;
    private $combatSecs;
    private $enderpearlSecs;
    private $antiSpamSecs;
    private $deviceOs;
    private $input;
    private $duelSpamSec;
    private $noDamageTick;

    //ARRAYS

    private $currentFormData;

    private $cps = [];

    //OTHER
    private $fishing;
    private $duelResultInvs;

    /* @var Scoreboard|null */
    private $scoreboard;

    /* @var \pocketmine\entity\Skin|null */
    //private $originalSkin;

    /* @var DisguiseInfo|null */
    //private $disguise;

    public function __construct($player) {

        //$this->originalSkin = null;

        if($player instanceof Player){
            $this->playerName = $player->getName();
           // $this->originalSkin = $player->getSkin();
        } else if (is_string($player)){
            $this->playerName = $player;
        }

        $this->currentName = $this->playerName;

        $this->inCombat = false;
        $this->canThrowPearl = true;
        $this->hasKit = false;
        $this->antiSpam = false;
        $this->canHitPlayer = false;
        $this->isLookingAtForm = false;

        $this->currentArena = PracticeArena::NO_ARENA;

        $this->currentSec = 0;
        $this->antiSendSecs = 0;
        $this->lastSecHit = 0;
        $this->combatSecs = 0;
        $this->enderpearlSecs = 0;
        $this->antiSpamSecs = 0;
        $this->deviceOs = -1;
        $this->input = -1;
        $this->duelSpamSec = 0;
        $this->noDamageTick = 0;
        $this->invId = -1;

        $this->currentFormData = [];

        $this->fishing = null;
        $this->duelResultInvs = [];

        $this->scoreboard = null;

        //$this->disguise = null;
    }

    /*public function saveOriginalSkin(Player $player) : self {
        $this->originalSkin = $player->getSkin();
        return $this;
    }

    public function hasDisguise() : bool {
        return !is_null($this->disguise);
    }

    public function setDisguise(DisguiseInfo $info) : void {
        $this->disguise = $info;
    }

    public function getDisguise() {
        return $this->disguise;
    }*/

    public function setNoDamageTicks(int $del) : void {
        $this->noDamageTick = $del;
    }

    public function getNoDamageTicks() : int {
        return $this->noDamageTick;
    }

    public function updatePlayer() : void {

        $this->currentSec++;

        $this->updateCps();

        if($this->isOnline() and !$this->isInArena()) {

            $p = $this->getPlayer();
            $level = $p->getLevel();

            if($this->currentSec % 5 === 0) {

                $resetHunger = PracticeUtil::areLevelsEqual($level, PracticeUtil::getDefaultLevel());

                if ($resetHunger === false and $this->isInDuel()) {
                    $duel = PracticeCore::getDuelHandler()->getDuel($this->playerName);
                    $resetHunger = PracticeUtil::equals_string($duel->getQueue(), 'Sumo', 'SumoPvP', 'sumo');
                }

                if ($resetHunger === true) {
                    $p->setFood($p->getMaxFood());
                    $p->setSaturation(Attribute::getAttribute(Attribute::SATURATION)->getMaxValue());
                }
            }
        }

        if(PracticeUtil::isEnderpearlCooldownEnabled()) {
            if(!$this->canThrowPearl()) {
                $this->removeSecInThrow();
                if($this->enderpearlSecs <= 0)
                    $this->setThrowPearl(true);
            }
        }

        if($this->isInAntiSpam()){
            $this->antiSpamSecs--;
            if($this->antiSpamSecs <= 0) $this->setInAntiSpam(false);
        }

        if($this->isInCombat()){
            $this->combatSecs--;
            if($this->combatSecs <= 0){
                $this->setInCombat(false);
            }
        }

        $sb = $this->getCurrentScoreboard();

        if($this->isInDuel() or $sb === Scoreboard::FFA_SCOREBOARD) $this->updateScoreboard();

        if($this->canSendDuelRequest() !== true) $this->duelSpamSec--;
    }

    public function updateNoDmgTicks() : void {
        if($this->noDamageTick > 0) {
            $this->noDamageTick--;
            if($this->noDamageTick <= 0)
                $this->noDamageTick = 0;
        }
    }

    public function initScoreboard(int $device) : void {
        if($this->isOnline()) {
            $this->scoreboard = $this->getSpawnScoreboard($device);
            $this->scoreboard->send();
        }
    }

    public function setScoreboard(string $type = '', bool $enable = false) : void {

        if(PracticeCore::getPlayerHandler()->isScoreboardEnabled($this->playerName)) {

            $defaultType = Scoreboard::SPAWN_SCOREBOARD;

            $set = false;

            if (Scoreboard::isValidBoardType($type) and !is_null($this->scoreboard)) {
                $currentType = $this->scoreboard->getType();
                if ($currentType !== $type) {
                    $defaultType = $type;
                    $set = true;
                } else $this->updateScoreboard();
            }

            if ($type === Scoreboard::NO_SCOREBOARD) {
                $this->scoreboard->remove();
                $this->scoreboard = null;
                return;
            }

            if ($set === true) {

                $this->scoreboard->remove();

                $sb = $this->getScoreboardFromType($defaultType);

                if(!is_null($sb)) $this->scoreboard = $sb;

                $this->scoreboard->send();
            }
        } else {

            if($enable === true) {

                $sb = $this->getScoreboardFromType($type);

                if(!is_null($sb)) {
                    $this->scoreboard = $sb;
                    $this->scoreboard->send();
                }
            } else {

                if (!is_null($this->scoreboard))
                    $this->scoreboard->remove();

                $this->scoreboard = null;
            }
        }
    }

    /**
     * @param string $type
     * @return Scoreboard|null
     */
    private function getScoreboardFromType(string $type) {

        $arr = [
            Scoreboard::SPAWN_SCOREBOARD => $this->getSpawnScoreboard($this->getDevice()),
            Scoreboard::FFA_SCOREBOARD => $this->getFFAScoreboard(),
            Scoreboard::DUEL_SCOREBOARD => $this->getDuelScoreboard(),
            Scoreboard::SPEC_SCOREBOARD => $this->getSpectatorScoreboard()
        ];

        return isset($arr[$type]) ? $arr[$type] : null;
    }

    public function updateScoreboard(string $key = '', array $values = []) : void {

        if(!is_null($this->scoreboard)) {

            $currentType = $this->scoreboard->getType();

            $size = count($values);

            if ($size === 0 and $key === '') {

                $lines = [];

                $sb = $this->getScoreboardFromType($currentType);

                if(!is_null($sb)) $lines = $sb->getLines();

                if (count($lines) > 0)
                    $this->scoreboard->resendAll($lines);

            } else $this->scoreboard->resendLine($key, $values);
        }
    }

    public function getCurrentScoreboard() : string {

        $type = Scoreboard::NO_SCOREBOARD;

        if(!is_null($this->scoreboard))
            $type = $this->scoreboard->getType();

        return $type;
    }

    private function getSpawnScoreboard(int $device) : Scoreboard {

        $duelHandler = PracticeCore::getDuelHandler();

        $server = Server::getInstance();

        $scoreboard = new Scoreboard($this->getPlayer(), $device, PracticeUtil::getName('server-name'), Scoreboard::SPAWN_SCOREBOARD);

        $scoreboard = $scoreboard->addSeparator(TextFormat::BLUE)
            ->addLine('online-players', PracticeUtil::getName('scoreboard.spawn.online-players'))
            ->addLine('in-fights', PracticeUtil::getName('scoreboard.spawn.in-fights'))
            ->addLine('in-queues', PracticeUtil::getName('scoreboard.spawn.in-queues'))
            ->addSeparator(TextFormat::RED)
            ->addLine('your-queue', PracticeUtil::getName('scoreboard.spawn.thequeue'))
            ->addSeparator(TextFormat::GREEN);

        $queue = $duelHandler->isPlayerInQueue($this->getPlayerName());

        if($queue === false)
            $scoreboard = $scoreboard->hideLine('your-queue')->hideLine('separator-3');
        else $scoreboard = $scoreboard->showLine('your-queue')->showLine('separator-3');

        $online = count($server->getOnlinePlayers());
        $max_online = $server->getMaxPlayers();

        $in_fights = PracticeCore::getPlayerHandler()->getPlayersInFights();;
        $in_queues = $duelHandler->getNumberOfQueuedPlayers();

        $scoreboard = $scoreboard->updateLine('online-players', ['%num%' => $online, '%max-num%' => $max_online])
                        ->updateLine('in-fights', ['%num%' => $in_fights])
                        ->updateLine('in-queues', ['%num%' => $in_queues]);

        if($duelHandler->isPlayerInQueue($this->playerName)) {

            $queue = $duelHandler->getQueuedPlayer($this->playerName);

            $ranked = ($queue->isRanked()) ? 'Ranked' : 'Unranked';

            $theQueue = $queue->getQueue();

            $scoreboard = $scoreboard->updateLine('your-queue', ['%queue%' => $theQueue, '%ranked%' => $ranked]);
        }

        return $scoreboard;
    }

    private function getDuelScoreboard() : Scoreboard {

        $scoreboard = new Scoreboard($this->getPlayer(), $this->getDevice(), PracticeUtil::getName('server-name'), Scoreboard::DUEL_SCOREBOARD);
        $scoreboard = $scoreboard->addSeparator(TextFormat::BLUE)
            ->addLine('opponent', PracticeUtil::getName('scoreboard.duels.opponent'))
            ->addLine('duration', PracticeUtil::getName('scoreboard.duels.duration'))
            ->addSeparator(TextFormat::RED)
            ->addLine('your-cps', PracticeUtil::getName('scoreboard.player.cps'))
            ->addLine('their-cps', PracticeUtil::getName('scoreboard.opponent.cps'))
            ->addSeparator(TextFormat::GREEN);

        if($this->isInDuel()) {

            $duel = PracticeCore::getDuelHandler()->getDuel($this->playerName);

            $opponent = ($duel->isOpponent($this->playerName)) ? $duel->getPlayer() : $duel->getOpponent();

            if(!is_null($opponent)) {

                $oppName = $opponent->getPlayerName();

                $duration = $duel->getDurationString();

                $scoreboard = $scoreboard->updateLine('opponent', ['%player%' => $oppName])
                    ->updateLine('duration', ['%time%' => $duration])
                    ->updateLine('your-cps', ['%player%' => 'Your', '%clicks%' => 0])
                    ->updateLine('their-cps', ['%player%' => 'Their', '%clicks%' => 0]);
            }
        }

        return $scoreboard;
    }

    private function getSpectatorScoreboard() : Scoreboard {

        $duelHandler = PracticeCore::getDuelHandler();

        $scoreboard = new Scoreboard($this->getPlayer(), $this->getDevice(), PracticeUtil::getName('server-name'), Scoreboard::SPEC_SCOREBOARD);

        $scoreboard = $scoreboard->addSeparator(TextFormat::GREEN)
            ->addLine('queue', PracticeUtil::getName('scoreboard.duels.kit'))
            ->addLine('duration', PracticeUtil::getName('scoreboard.duels.duration'))
            ->addSeparator(TextFormat::BLUE);

        if($duelHandler->isASpectator($this->playerName)) {
            $duel = $duelHandler->getDuelFromSpec($this->playerName);
            $duration = $duel->getDurationString();
            $queue = $duel->getQueue();
            $scoreboard = $scoreboard->updateLine('queue', ['%kit%' => $queue])
                ->updateLine('duration', ['%time%' => $duration]);
        }

        return $scoreboard;
    }

    private function getFFAScoreboard() : Scoreboard {

        $scoreboard = new Scoreboard($this->getPlayer(), $this->getDevice(), PracticeUtil::getName('server-name'), Scoreboard::FFA_SCOREBOARD);

        $scoreboard = $scoreboard->addSeparator(TextFormat::GOLD)
            ->addLine('arena', PracticeUtil::getName('scoreboard.arena-ffa.arena'))
            ->addSeparator(TextFormat::GREEN)
            ->addLine('cps', PracticeUtil::getName('scoreboard.player.cps'))
            ->addLine('kills', PracticeUtil::getName('scoreboard.arena-ffa.kills'))
            ->addLine('deaths', PracticeUtil::getName('scoreboard.arena-ffa.deaths'))
            ->addSeparator(TextFormat::BLUE);

        if($this->isInArena()) {

            $playerHandler = PracticeCore::getPlayerHandler();

            $arena = $this->currentArena;

            $name = PracticeUtil::getName('scoreboard.arena-ffa.arena');

            if(PracticeUtil::str_contains(' FFA', $arena) and PracticeUtil::str_contains(' FFA', $name))
                $arena = PracticeUtil::str_replace($arena, [' FFA' => '']);

            $cps = count($this->cps);
            $kills = $playerHandler->getKillsOf($this->playerName);
            $deaths = $playerHandler->getDeathsOf($this->playerName);
            $scoreboard = $scoreboard->updateLine('arena', ['%arena%' => $arena])
                ->updateLine('cps', ['%player%' => 'Your', '%clicks%' => $cps])
                ->updateLine('kills', ['%num%' => $kills])
                ->updateLine('deaths', ['%num%' => $deaths]);
        }
        return $scoreboard;
    }

    public function setCantSpamDuel() : void {
        //$this->duelSpamTick = PracticeUtil::ticksToSeconds(20);
        $this->duelSpamSec = 20;
    }

    public function getCantDuelSpamSecs() : int {
        return $this->duelSpamSec;
    }

    public function canSendDuelRequest() : bool {
        return $this->duelSpamSec <= 0;
    }

    public function hasDuelInvs() : bool {
        return count($this->duelResultInvs) > 0;
    }

    public function hasInfoOfLastDuel() : bool {
        return $this->hasDuelInvs() and count($this->getInfoOfLastDuel()) > 0;
    }

    public function getInfoOfLastDuel() : array {

        $count = count($this->duelResultInvs);

        return ($count > 0) ? $this->duelResultInvs[$count - 1] : [];
    }

    public function addToDuelHistory(DuelInvInfo $player, DuelInvInfo $opponent) : void {
        $this->duelResultInvs[] = ['player' => $player, 'opponent' => $opponent];
    }

    public function isDuelHistoryItem(Item $item) : bool {

        $result = false;

        if($this->hasInfoOfLastDuel()) {

            $pInfo = $this->getInfoOfLastDuel()['player'];
            $oInfo = $this->getInfoOfLastDuel()['opponent'];

            if($pInfo instanceof DuelInvInfo and $oInfo instanceof DuelInvInfo)
                $result = ($pInfo->getItem()->equalsExact($item) or $oInfo->getItem()->equalsExact($item));
        }
        return $result;
    }

    public function spawnResInvItems() : void {

        if($this->isOnline()) {

            $inv = $this->getPlayer()->getInventory();

            if ($this->hasInfoOfLastDuel()) {

                $res = $this->getInfoOfLastDuel();

                $p = $res['player'];
                $o = $res['opponent'];

                if ($p instanceof DuelInvInfo and $o instanceof DuelInvInfo) {

                    $inv->clearAll();

                    $exitItem = PracticeCore::getItemHandler()->getExitInventoryItem();

                    $slot = $exitItem->getSlot();

                    $item = $exitItem->getItem();

                    $inv->setItem(0, $p->getItem());

                    $inv->setItem(1, $o->getItem());

                    $inv->setItem($slot, $item);
                }

            } else $this->sendMessage(PracticeUtil::getMessage('view-res-inv-msg'));

        }
    }

    public function startFishing() : void {

        if($this->isOnline()) {

            $player = $this->getPlayer();

            if($player !== null) {

                $tag = Entity::createBaseNBT($player->add(0.0, $player->getEyeHeight(), 0.0), $player->getDirectionVector(), floatval($player->yaw), floatval($player->pitch));
                $rod = Entity::createEntity('FishingHook', $player->getLevel(), $tag, $player);

                if ($rod !== null) {
                    $x = -sin(deg2rad($player->yaw)) * cos(deg2rad($player->pitch));
                    $y = -sin(deg2rad($player->pitch));
                    $z = cos(deg2rad($player->yaw)) * cos(deg2rad($player->pitch));
                    $rod->setMotion(new Vector3($x, $y, $z));
                }

                //$item->count--;

                if (!is_null($rod) and $rod instanceof FishingHook) {
                    $ev = new ProjectileLaunchEvent($rod);
                    $ev->call();
                    if ($ev->isCancelled()) {
                        $rod->flagForDespawn();
                    } else {
                        $rod->spawnToAll();
                        $this->fishing = $rod;
                        $player->getLevel()->broadcastLevelSoundEvent($player, LevelSoundEventPacket::SOUND_THROW, 0, EntityIds::PLAYER);
                    }
                }
            }
        }
    }

    public function stopFishing(bool $click = true) : void {

        if($this->isFishing()) {

            if($this->fishing instanceof FishingHook) {
                $rod = $this->fishing;
                if($click) {
                    $rod->reelLine();
                } elseif ($rod !== null) {
                    $rod->kill();
                    $rod->close();
                }
            }
        }
        $this->fishing = null;
    }

    public function isFishing() : bool {
        return $this->fishing !== null;
    }

    public function isInAntiSpam() : bool {
        return $this->antiSpam;
    }

    public function setInAntiSpam(bool $res) : void {
        $this->antiSpam = $res;
        if($this->antiSpam === true)
            $this->antiSpamSecs = 5;
        else $this->antiSpamSecs = 0;
    }

    public function getCurrentSec() : int { return $this->currentSec; }

    public function isInvisible() : bool {
        return $this->getPlayer()->isInvisible();
    }

    public function setInvisible(bool $res) : void {
        if($this->isOnline()) $this->getPlayer()->setInvisible($res);
    }

    public function setHasKit(bool $res) : void {
        $this->hasKit = $res;
    }

    public function doesHaveKit() : bool { return $this->hasKit; }

    public function getPlayerName() : string { return $this->playerName; }

    public function getPlayer() { return Server::getInstance()->getPlayer($this->playerName); }

    public function isOnline() : bool { return isset($this->playerName) and !is_null($this->getPlayer()) and $this->getPlayer()->isOnline(); }

    public function setInCombat(bool $res) : void {

        if($res === true){
            $this->lastSecHit = $this->currentSec;
            $this->combatSecs = self::MAX_COMBAT_TICKS;
            if($this->isOnline()){
                $p = $this->getPlayer();
                if($this->inCombat === false)
                    $p->sendMessage(PracticeUtil::getMessage('general.combat.combat-place'));
            }
        } else {
            $this->combatSecs = 0;
            if($this->isOnline()){
                $p = $this->getPlayer();
                if($this->inCombat === true)
                    $p->sendMessage(PracticeUtil::getMessage('general.combat.combat-remove'));

            }
        }
        $this->inCombat = $res;
    }

    public function isInCombat() : bool { return $this->inCombat; }

    public function getLastSecInCombat() : int { return $this->lastSecHit; }

    private function removeSecInThrow() : void {
        $this->enderpearlSecs--;
        $maxSecs = self::MAX_ENDERPEARL_SECONDS;
        $sec = $this->enderpearlSecs;
        if($sec < 0) $sec = 0;
        if($this->enderpearlSecs < 0) $this->enderpearlTicks = 0;
        $percent = floatval($this->enderpearlSecs / $maxSecs);
        if($this->isOnline()){
            $p = $this->getPlayer();
            $p->setXpLevel($sec);
            $p->setXpProgress($percent);
        }
    }

    public function canThrowPearl() : bool {
        return $this->canThrowPearl;
    }

    public function setThrowPearl(bool $res) : void {
        if($res === false){
            $this->enderpearlSecs = self::MAX_ENDERPEARL_SECONDS;
            if($this->isOnline()){
                $p = $this->getPlayer();
                if($this->canThrowPearl === true)
                    $p->sendMessage(PracticeUtil::getMessage('general.enderpearl-cooldown.cooldown-place'));

                $p->setXpProgress(1.0);
                $p->setXpLevel(self::MAX_ENDERPEARL_SECONDS);
            }
        } else {
            $this->enderpearlSecs = 0;
            if($this->isOnline()){
                $p = $this->getPlayer();
                if($this->canThrowPearl === false)
                    $p->sendMessage(PracticeUtil::getMessage('general.enderpearl-cooldown.cooldown-remove'));

                $p->setXpLevel(0);
                $p->setXpProgress(0);
            }
        }
        $this->canThrowPearl = $res;
    }

    public function sendMessage(string $msg) : void {
        if($this->isOnline()){
            $p = $this->getPlayer();
            $p->sendMessage($msg);
        }
    }

    public function isInArena() : bool {
        return $this->currentArena !== PracticeArena::NO_ARENA;
    }

    public function setCurrentArena(string $currentArena): void {
        $this->currentArena = $currentArena;
    }

    public function getCurrentArena() {
        return PracticeCore::getArenaHandler()->getArena($this->currentArena);
    }

    public function getCurrentArenaType() : string {

        $type = PracticeArena::NO_ARENA;

        $arena = $this->getCurrentArena();

        if($this->isInArena() and !is_null($arena))
            $type = $arena->getArenaType();

        return $type;
    }

    public function teleportToFFA(FFAArena $arena) {

        if($this->isOnline()) {

            $player = $this->getPlayer();
            $spawn = $arena->getSpawnPosition();
            $msg = null;

            $duelHandler = PracticeCore::getDuelHandler();

            if($duelHandler->isPlayerInQueue($player))
                $duelHandler->removePlayerFromQueue($player, true);

            if(!is_null($spawn)) {

                PracticeUtil::onChunkGenerated($spawn->level, intval($spawn->x) >> 4, intval($spawn->z) >> 4, function() use($player, $spawn) {
                    $player->teleport($spawn);
                });

                $arenaName = $arena->getName();
                $this->currentArena = $arenaName;

                if($arena->doesHaveKit()) {
                    $kit = $arena->getFirstKit();
                    $kit->giveTo($this, true);
                }

                $this->setCanHitPlayer(true);
                $msg = PracticeUtil::getMessage('general.arena.join');
                $msg = strval(str_replace('%arena-name%', $arenaName, $msg));
                $this->setScoreboard(Scoreboard::FFA_SCOREBOARD);

            } else {

                $msg = PracticeUtil::getMessage('general.arena.fail');
                $msg = strval(str_replace('%arena-name%', $arena->getName(), $msg));
            }

            if(!is_null($msg)) $player->sendMessage($msg);
        }
    }

    public function canHitPlayer() : bool {
        return $this->canHitPlayer;
    }

    public function setCanHitPlayer(bool $res) : void {
        $p = $this->getPlayer();
        if($this->isOnline()) PracticeUtil::setCanHit($p, $res);
        $this->canHitPlayer = $res;
    }

    private function updateCps() : void {

        $cps = $this->cps;

        $microtime = microtime(true);

        $keys = array_keys($cps);

        foreach($keys as $key)  {
            $thecps = floatval($key);
            if($microtime - $thecps > 1)
                unset($cps[$key]);
        }

        $this->cps = $cps;
    }

    public function addCps(bool $clickedBlock): void {

        $microtime = microtime(true);

        $keys = array_keys($this->cps);

        $size = count($keys);

        foreach($keys as $key) {
            $cps = floatval($key);
            if($microtime - $cps > 1)
                unset($this->cps[$key]);
        }

        if($clickedBlock === true and $size > 0) {
            $index = $size - 1;
            $lastKey = $keys[$index];
            $cps = floatval($lastKey);
            if(isset($this->cps[$lastKey])) {
                $val = $this->cps[$lastKey];
                $diff = $microtime - $cps;
                if ($val === true and $diff <= 0.05)
                    unset($this->cps[$lastKey]);
            }
        }

        $this->cps["$microtime"] = $clickedBlock;

        $yourCPS = count($this->cps);

        $sb = $this->getCurrentScoreboard();

        if($this->isInDuel()) {

            $duel = PracticeCore::getDuelHandler()->getDuel($this->playerName);

            if($duel->isDuelRunning() and $duel->arePlayersOnline()) {

                $other = $duel->isPlayer($this->playerName) ? $duel->getOpponent() : $duel->getPlayer();

                $this->updateScoreboard('your-cps', ['%player%' => 'Your', '%clicks%' => $yourCPS]);
                $other->updateScoreboard('their-cps', ['%player%' => 'Their', '%clicks%' => $yourCPS]);

            }
        } elseif ($sb === Scoreboard::FFA_SCOREBOARD)
            $this->updateScoreboard('cps', ['%player%' => 'Your', '%clicks%' => $yourCPS]);
    }

    public function getInput() : int {
        return $this->input;
    }

    public function getDevice() : int {
        return $this->deviceOs;
    }

    public function setInput(int $val) : void {
        if($this->input === -1)
            $this->input = $val;
    }

    public function setDeviceOS(int $val) : void {
        if($this->deviceOs === PracticeUtil::UNKNOWN)
            $this->deviceOs = $val;
    }

    public function peOnlyQueue() : bool {
        return $this->deviceOs !== PracticeUtil::WINDOWS_10 and $this->input === PracticeUtil::CONTROLS_TOUCH;
    }

    public function isInDuel() : bool {
        return PracticeCore::getDuelHandler()->isInDuel($this->playerName);
    }

    public function isInParty() : bool {
        return PracticeCore::getPartyManager()->isPlayerInParty($this->playerName);
    }

    public function canUseCommands(bool $sendMsg = true) : bool {
        $result = false;
        if($this->isOnline()){
            $msg = null;
            if($this->isInDuel()){
                $msgStr = ($this->isInCombat()) ? 'general.combat.command-msg' : 'general.duels.command-msg';
                $msg = PracticeUtil::getMessage($msgStr);
            } else {
                if($this->isInCombat())
                    $msg = PracticeUtil::getMessage('general.combat.command-msg');
                else $result = true;
            }
            if(!is_null($msg) and $sendMsg) $this->getPlayer()->sendMessage($msg);
        }
        return $result;
    }

    public function getPing() : int {
        $ping = $this->getPlayer()->getPing() - 20;
        if($ping < 0) $ping = 0;
        return $ping;
    }

    public function placeInDuel(DuelGroup $grp) : void {

        if($this->isOnline()) {

            $p = $this->getPlayer();

            $arena = $grp->getArena();

            $isPlayer = $grp->isPlayer($this->playerName);

            $pos = ($isPlayer === true) ? $arena->getPlayerPos() : $arena->getOpponentPos();

            $oppName = ($isPlayer === true) ? $grp->getOpponent()->getPlayerName() : $grp->getPlayer()->getPlayerName();

            $p->setGamemode(0);

            PracticeUtil::onChunkGenerated($pos->level, intval($pos->x) >> 4, intval($pos->z) >> 4, function() use($p, $pos) {
                $p->teleport($pos);
            });

            //$p->teleport($pos);

            $queue = $grp->getQueue();

            if($arena->hasKit($queue)){
                $kit = $arena->getKit($queue);
                $kit->giveTo($p);
            }

            $this->setCanHitPlayer(true);

            PracticeUtil::setFrozen($p, true, true);

            $ranked = $grp->isRanked() ? 'Ranked' : 'Unranked';
            $countdown = DuelGroup::MAX_COUNTDOWN_SEC;

            $p->sendMessage(PracticeUtil::str_replace(PracticeUtil::getMessage('duels.start.msg2'), ['%map%' => $grp->getArenaName()]));
            $p->sendMessage(PracticeUtil::str_replace(PracticeUtil::getMessage('duels.start.msg1'), ['%seconds%' => $countdown, '%ranked%' => $ranked, '%queue%' => $queue, '%player%' => $oppName]));
        }
    }

    public function sendForm(Form $form, bool $isDuelForm = false, bool $ranked = false) {

        if($this->isOnline() and !$this->isLookingAtForm) {

            $p = $this->getPlayer();

            $formToJSON = $form->jsonSerialize();

            $content = [];

            if(isset($formToJSON['content']) and is_array($formToJSON['content']))
                $content = $formToJSON['content'];
            elseif (isset($formToJSON['buttons']) and is_array($formToJSON['buttons']))
                $content = $formToJSON['buttons'];

            if($isDuelForm === true) $content['ranked'] = $ranked;

            $exec = true;

            if($form instanceof SimpleForm) {

                if($form->getTitle() === FormUtil::getFFAForm()->getTitle()) {

                    $size = count(PracticeCore::getArenaHandler()->getFFAArenas());

                    $exec = $size > 0;
                }
            }

            if($exec === true) {

                $this->currentFormData = $content;

                $this->isLookingAtForm = true;

                $p->sendForm($form);

            } else $this->sendMessage(TextFormat::RED . 'Failed to open form.');
        }
    }

    public function removeForm() : array {
        $this->isLookingAtForm = false;
        $data = $this->currentFormData;
        /*if(is_string($this->currentFormData))
            $data = [$this->currentFormData];
        elseif (is_array($this->currentFormData))
            $data = $this->currentFormData;*/
        $this->currentFormData = [];
        return $data;
    }

    public function equals($object) : bool {

        $result = false;

        if($object instanceof PracticePlayer)

            $result = $object->getPlayerName() === $this->playerName;

        return $result;
    }

    /* --------------------------------------------- ANTI CHEAT FUNCTIONS ---------------------------------------------*/

    public function kick(string $msg) : void {
        if($this->isOnline()) {
            $p = $this->getPlayer();
            $p->getInventory()->clearAll();
            $p->kick($msg);
        }
    }
}