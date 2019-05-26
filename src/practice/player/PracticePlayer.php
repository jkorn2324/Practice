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
use practice\anticheat\AntiCheatUtil;
use practice\arenas\FFAArena;
use practice\arenas\PracticeArena;
use practice\duels\groups\DuelGroup;
use practice\duels\misc\DuelInvInfo;
use practice\game\entity\FishingHook;
use practice\game\FormUtil;
use practice\player\disguise\DisguiseInfo;
use practice\player\info\PlayerCPSInfo;
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
    private $setInAntiSpam;
    private $canHitPlayer;
    private $isLookingAtForm;
    private $invId;

    //STRINGS
    private $playerName;
    private $currentName;
    private $currentArena;

    //INTEGERS
    private $currentTick;
    private $antiSendTicks;
    private $lastTickHit;
    private $combatTicks;
    private $enderpearlTicks;
    private $lastAntiSpamTick;
    private $deviceOs;
    private $input;
    private $duelSpamTick;
    private $noDamageTick;

    //ARRAYS

    /* @var PlayerClick[] */
    private $clicks;
    private $currentFormData;

    //OTHER
    private $fishing;
    private $duelResultInvs;

    /* @var Scoreboard|null */
    private $scoreboard;

    /* @var \pocketmine\entity\Skin|null */
    //private $originalSkin;

    /* @var DisguiseInfo|null */
    //private $disguise;

    //ANTICHEAT VARS

    /* @var PlayerCPSInfo[] */
    private $cpsHistory;



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
        $this->setInAntiSpam = false;
        $this->canHitPlayer = false;
        $this->isLookingAtForm = false;

        $this->currentArena = PracticeArena::NO_ARENA;

        $this->currentTick = 0;
        $this->antiSendTicks = 0;
        $this->lastTickHit = 0;
        $this->combatTicks = 0;
        $this->enderpearlTicks = 0;
        $this->lastAntiSpamTick = 0;
        $this->deviceOs = -1;
        $this->input = -1;
        $this->duelSpamTick = 0;
        $this->noDamageTick = 0;
        $this->invId = -1;

        $this->clicks = array();
        $this->currentFormData = array();

        $this->fishing = null;
        $this->duelResultInvs = array();

        $this->scoreboard = null;

        $this->cpsHistory = [];

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

    public function getNoDamageTicks() : int {
        return $this->noDamageTick;
    }

    public function updatePlayer() : void {

        $this->currentTick++;

        if($this->currentTick % 5 === 0 and $this->currentTick !== 0) {
            $info = new PlayerCPSInfo($this->currentTick, $this->getCps());
            $this->cpsHistory[] = $info;
        }

        if($this->isOnline() and !$this->isInArena()) {

            $p = $this->getPlayer();
            $level = $p->getLevel();

            $seconds = PracticeUtil::secondsToTicks(3);

            if($this->currentTick % $seconds === 0) {

                $resetHunger = PracticeUtil::areLevelsEqual($level, PracticeUtil::getDefaultLevel());

                if($resetHunger === false and $this->isInDuel()) {
                    $duel = PracticeCore::getDuelHandler()->getDuel($this->playerName);
                    $resetHunger = PracticeUtil::equals_string($duel->getQueue(), 'Sumo', 'SumoPvP', 'sumo');
                }

                if ($resetHunger === true) {
                    $p->setFood($p->getMaxFood());
                    $p->setSaturation(Attribute::getAttribute(Attribute::SATURATION)->getMaxValue());
                }
            }

        }

        if(PracticeUtil::isEnderpearlCooldownEnabled()){
            if(!$this->canThrowPearl()){
                $this->removeTickInThrow(1);

                if($this->getEnderpearlTicks() <= 0)
                    $this->setThrowPearl(true);
            }
        }

        if($this->isInAntiSpam()){
            $ticks = $this->getTicksInAntiSpam();

            $seconds = PracticeUtil::secondsToTicks(5);

            if($ticks >= $seconds)
                $this->setInAntiSpam(false);

        }

        if($this->isInCombat()){
            $this->combatTicks--;
            if($this->combatTicks <= 0){
                $this->setInCombat(false);
            }
        }

        if($this->noDamageTick > 0) {
            $this->noDamageTick--;
            if($this->noDamageTick <= 0)
                $this->noDamageTick = 0;

        }

        if($this->isInArena() and $this->currentTick % 5 === 0) $this->updateScoreboard();

        if($this->canSendDuelRequest() !== true) $this->duelSpamTick--;

        if($this->currentTick % 10 === 0 and $this->currentTick !== 0) {

            if ($this->isAutoClicking())
                //TODO CHANGE TO BAN
                $this->kick('Autoclicking');
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

        $scoreboard = new Scoreboard($this->getPlayer(), $device, PracticeUtil::getName('server-name'), Scoreboard::SPAWN_SCOREBOARD);

        $scoreboard = $scoreboard->addSeparator(TextFormat::BLUE)
            ->addLine('online-players', PracticeUtil::getName('scoreboard.spawn.online-players'))
            ->addLine('in-fights', PracticeUtil::getName('scoreboard.spawn.in-fights'))
            ->addLine('in-queues', PracticeUtil::getName('scoreboard.spawn.in-queues'))
            ->addSeparator(TextFormat::RED)
            ->addLine('your-queue', PracticeUtil::getName('scoreboard.spawn.thequeue'))
            ->addSeparator(TextFormat::GREEN);

        $queue = PracticeCore::getDuelHandler()->isPlayerInQueue($this->getPlayerName());

        if($queue === false)
            $scoreboard = $scoreboard->hideLine('your-queue')->hideLine('separator-3');
        else $scoreboard = $scoreboard->showLine('your-queue')->showLine('separator-3');

        $online = count(Server::getInstance()->getOnlinePlayers());
        $max_online = Server::getInstance()->getMaxPlayers();

        $in_fights = PracticeCore::getPlayerHandler()->getPlayersInFights();;
        $in_queues = PracticeCore::getDuelHandler()->getNumberOfQueuedPlayers();

        $scoreboard = $scoreboard->updateLine('online-players', ['%num%' => $online, '%max-num%' => $max_online])
                        ->updateLine('in-fights', ['%num%' => $in_fights])
                        ->updateLine('in-queues', ['%num%' => $in_queues]);

        if(PracticeCore::getDuelHandler()->isPlayerInQueue($this->playerName)) {

            $queue = PracticeCore::getDuelHandler()->getQueuedPlayer($this->playerName);

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

                $oppCps = $opponent->getCps();

                $yourCps = $this->getCps();

                $duration = $duel->getDurationString();

                $scoreboard = $scoreboard->updateLine('opponent', ['%player%' => $oppName])
                    ->updateLine('duration', ['%time%' => $duration])
                    ->updateLine('your-cps', ['%player%' => 'Your', '%clicks%' => $yourCps])
                    ->updateLine('their-cps', ['%player%' => 'Their', '%clicks%' => $oppCps]);
            }
        }

        return $scoreboard;
    }

    private function getSpectatorScoreboard() : Scoreboard {

        $scoreboard = new Scoreboard($this->getPlayer(), $this->getDevice(), PracticeUtil::getName('server-name'), Scoreboard::SPEC_SCOREBOARD);

        $scoreboard = $scoreboard->addSeparator(TextFormat::GREEN)
            ->addLine('queue', PracticeUtil::getName('scoreboard.duels.kit'))
            ->addLine('duration', PracticeUtil::getName('scoreboard.duels.duration'))
            ->addSeparator(TextFormat::BLUE);

        if(PracticeCore::getDuelHandler()->isASpectator($this->playerName)) {
            $duel = PracticeCore::getDuelHandler()->getDuelFromSpec($this->playerName);
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

            $arena = $this->currentArena;

            $name = PracticeUtil::getName('scoreboard.arena-ffa.arena');

            if(PracticeUtil::str_contains(' FFA', $arena) and PracticeUtil::str_contains(' FFA', $name))
                $arena = PracticeUtil::str_replace($arena, [' FFA' => '']);

            $cps = $this->getCps();
            $kills = PracticeCore::getPlayerHandler()->getKillsOf($this->playerName);
            $deaths = PracticeCore::getPlayerHandler()->getDeathsOf($this->playerName);
            $scoreboard = $scoreboard->updateLine('arena', ['%arena%' => $arena])
                ->updateLine('cps', ['%player%' => 'Your', '%clicks%' => $cps])
                ->updateLine('kills', ['%num%' => $kills])
                ->updateLine('deaths', ['%num%' => $deaths]);
        }
        return $scoreboard;
    }

    public function setCantSpamDuel() : void {
        $this->duelSpamTick = PracticeUtil::ticksToSeconds(20);
    }

    public function getCantSpamDuelTicks() : int {
        return $this->duelSpamTick;
    }

    public function canSendDuelRequest() : bool {
        return $this->duelSpamTick <= 0;
    }

    public function hasDuelInvs() : bool {
        return count($this->duelResultInvs) > 0;
    }

    public function hasInfoOfLastDuel() : bool {
        return $this->hasDuelInvs() and count($this->getInfoOfLastDuel()) > 0;
    }

    public function getInfoOfLastDuel() : array {

        $count = count($this->duelResultInvs);

        $result = [];

        if($count > 0) {
            $result = $this->duelResultInvs[$count - 1];
        }

        return $result;
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
        return $this->setInAntiSpam;
    }

    public function setInAntiSpam(bool $res) : void {
        if($res === true) $this->lastAntiSpamTick = $this->currentTick;
        $this->setInAntiSpam = $res;
    }

    private function getTicksInAntiSpam() : int {
        return $this->currentTick - $this->lastAntiSpamTick;
    }

    public function getCurrentTick() : int { return $this->currentTick; }

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
            $this->lastTickHit = $this->currentTick;
            $this->combatTicks = PracticeUtil::secondsToTicks(self::MAX_COMBAT_TICKS);
            if($this->isOnline()){
                $p = $this->getPlayer();
                if($this->inCombat === false){
                    $p->sendMessage(PracticeUtil::getMessage('general.combat.combat-place'));
                }
            }
        } else {
            $this->combatTicks = 0;
            if($this->isOnline()){
                $p = $this->getPlayer();
                if($this->inCombat === true){
                    $p->sendMessage(PracticeUtil::getMessage('general.combat.combat-remove'));
                }
            }
        }
        $this->inCombat = $res;
    }

    public function isInCombat() : bool { return $this->inCombat; }

    public function getLastTickInCombat() : int { return $this->lastTickHit; }

    public function getEnderpearlTicks() : int { return $this->enderpearlTicks; }

    public function removeTickInThrow(int $amount) : void {
        $this->enderpearlTicks -= $amount;
        if($this->enderpearlTicks % 20 === 0){
            $maxTicks = PracticeUtil::secondsToTicks(self::MAX_ENDERPEARL_SECONDS);
            $sec = PracticeUtil::ticksToSeconds($this->enderpearlTicks);
            if($sec < 0) $sec = 0;
            if($this->enderpearlTicks < 0) $this->enderpearlTicks = 0;
            $percent = floatval($this->enderpearlTicks / $maxTicks);
            if($this->isOnline()){
                $p = $this->getPlayer();
                $p->setXpLevel($sec);
                $p->setXpProgress($percent);
            }
        }
    }

    public function canThrowPearl() : bool {
        return $this->canThrowPearl;
    }

    public function setThrowPearl(bool $res) : void {
        if($res === false){
            $this->enderpearlTicks = PracticeUtil::secondsToTicks(self::MAX_ENDERPEARL_SECONDS);
            if($this->isOnline()){
                $p = $this->getPlayer();
                if($this->canThrowPearl === true){
                    $p->sendMessage(PracticeUtil::getMessage('general.enderpearl-cooldown.cooldown-place'));
                }
                $p->setXpProgress(1.0);
                $p->setXpLevel(self::MAX_ENDERPEARL_SECONDS);
            }
        } else {
            $this->enderpearlTicks = 0;
            if($this->isOnline()){
                $p = $this->getPlayer();
                if($this->canThrowPearl === false){
                    $p->sendMessage(PracticeUtil::getMessage('general.enderpearl-cooldown.cooldown-remove'));
                }
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

        if($this->isInArena() and !is_null($this->getCurrentArena()))
            $type = $this->getCurrentArena()->getArenaType();

        return $type;
    }

    public function teleportToFFA(FFAArena $arena) {

        if($this->isOnline()) {
            $player = $this->getPlayer();
            $spawn = $arena->getSpawnPosition();
            $msg = null;

            if(PracticeCore::getDuelHandler()->isPlayerInQueue($player))
                PracticeCore::getDuelHandler()->removePlayerFromQueue($player, true);

            if(!is_null($spawn)) {
                $player->teleport($spawn);
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

    public function addClick(bool $clickedBlock) {
        $type = ($clickedBlock === true ? PlayerClick::CLICK_BLOCK : PlayerClick::CLICK_AIR);
        $click = new PlayerClick($this->currentTick, $type);
        $exec = true;
        $size = count($this->clicks);
        if($size > 0){
            $lastClick = $this->clicks[$size - 1];

            if($lastClick instanceof PlayerClick)
                $exec = !$click->equals($lastClick);

        }
        if($exec === true) $this->clicks[] = $click;
    }

    public function getCps() : int {

        $count = 0;

        $size = count($this->clicks) - 1;

        for($i = $size; $i > -1; $i--) {
            $click = $this->clicks[$i];
            if($click instanceof PlayerClick){
                $difference = $this->currentTick - $click->getTickClicked();
                if($difference <= 20 and $difference > 0){
                    $count++;
                } else break;
            }
        }

        return $count;
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
                if($this->isInCombat()){
                    $msg = PracticeUtil::getMessage('general.combat.command-msg');
                } else {
                    $msg = PracticeUtil::getMessage('general.duels.command-msg');
                }
            } else {
                if($this->isInCombat()){
                    $msg = PracticeUtil::getMessage('general.combat.command-msg');
                } else {
                    $result = true;
                }
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

            $pos = $grp->isPlayer($this->playerName) ? $arena->getPlayerPos() : $arena->getOpponentPos();

            $oppName = $grp->isPlayer($this->playerName) ? $grp->getOpponent()->getPlayerName() : $grp->getPlayer()->getPlayerName();

            $p->setGamemode(0);

            $p->teleport($pos);

            if($arena->hasKit($grp->getQueue())){
                $kit = $arena->getKit($grp->getQueue());
                $kit->giveTo($p);
            }

            $this->setCanHitPlayer(true);

            PracticeUtil::setFrozen($p, true, true);

            $ranked = $grp->isRanked() ? 'Ranked' : 'Unranked';
            $countdown = DuelGroup::MAX_COUNTDOWN_SEC;

            $p->sendMessage(PracticeUtil::str_replace(PracticeUtil::getMessage('duels.start.msg2'), ['%map%' => $grp->getArenaName()]));
            $p->sendMessage(PracticeUtil::str_replace(PracticeUtil::getMessage('duels.start.msg1'), ['%seconds%' => $countdown, '%ranked%' => $ranked, '%queue%' => $grp->getQueue(), '%player%' => $oppName]));
        }
    }

    public function sendForm(Form $form, bool $isDuelForm = false, bool $ranked = false) {

        if($this->isOnline() and !$this->isLookingAtForm) {

            $p = $this->getPlayer();

            $content = $form->jsonSerialize()['content'];

            if($isDuelForm) $content['ranked'] = $ranked;

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

    public function isAutoClicking() : bool {

        return ($this->deviceOs === PracticeUtil::WINDOWS_10) ? $this->isCPSBetween15N20() : false;
    }


    public function isCPSBetween15N20() : bool {

        $suspectCPS = [];

        $clicks = [];

        $size = count($this->cpsHistory) - 1;

        for($i = $size; $i > -1; $i--) {

            $pastCPS = $this->cpsHistory[$i];
            $tick = $pastCPS->getTick();
            $difference = $this->currentTick - $tick;
            $seconds = PracticeUtil::ticksToSeconds($difference);

            if($difference > -1) {

                if($seconds <= 10) {

                    $clicks[] = $pastCPS;
                    $cps = $pastCPS->getCPS();

                    if($cps >= 15 and $cps <= 20)
                        $suspectCPS[] = $pastCPS;

                } else break;
            }
        }

        $suspectCPSLen = count($suspectCPS);

        $clicksLen = count($clicks);

        return $suspectCPSLen > 0 and $suspectCPSLen === $clicksLen;
    }

    /*private function doesCPSFluctuate() : bool {

        $initialVal = -1;

        $result = false;

        $size = count($this->cpsHistory) - 1;

        for($i = $size; $i > -1; $i--) {

            $pastCPS = $this->cpsHistory[$i];
            $tick = $pastCPS->getTick();
            $difference = $this->currentTick - $tick;
            $seconds = PracticeUtil::ticksToSeconds($difference);

            if($difference > -1) {

                if($seconds <= 5) {
                    $cps = $pastCPS->getCPS();

                    if ($initialVal === -1) $initialVal = $cps;
                    else {
                        if ($initialVal !== $cps) {
                            $result = true;
                            break;
                        }
                    }

                } else break;
            }
        }

        if($initialVal < 1) $result = true;

        return $result;
    }*/

    public function kick(string $msg) : void {
        if($this->isOnline()) {
            $this->getPlayer()->getInventory()->clearAll();
            $this->getPlayer()->kick($msg);
        }
    }
}