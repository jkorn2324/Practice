<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-18
 * Time: 10:31
 */

declare(strict_types=1);

namespace practice;


use pocketmine\block\Block;
use pocketmine\command\CommandSender;
use pocketmine\entity\Attribute;
use pocketmine\entity\Effect;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\EnderPearl;
use pocketmine\item\Item;
use pocketmine\item\Potion;
use pocketmine\item\SplashPotion;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\permission\PermissionManager;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Internet;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;
use practice\arenas\PracticeArena;
use practice\game\entity\FishingHook;
use practice\player\PracticePlayer;
use practice\ranks\Rank;
use practice\ranks\RankHandler;
use practice\scoreboard\Scoreboard;
use practice\scoreboard\ScoreboardUtil;

class PracticeUtil
{

    public const MOBILE_SEPARATOR_LEN = 27;
    public const WIN10_ADDED_SEPARATOR = '-----';
    public const PLUGIN_NAME = 'Practice';

    //LIST OF ALL DEVICES

    public const WINDOWS_10 = 7;
    public const IOS = 2;
    public const ANDROID = 1;
    public const WINDOWS_32 = 8;
    public const UNKNOWN = -1;
    public const MAC_EDU = 3;
    public const FIRE_EDU = 4;
    public const GEAR_VR = 5;
    public const HOLOLENS_VR = 6;
    public const DEDICATED = 9;
    public const ORBIS = 10;
    public const NX = 11;

    public const CONTROLS_UNKNOWN = 0;
    public const CONTROLS_MOUSE = 1;
    public const CONTROLS_TOUCH = 2;
    public const CONTROLS_CONTROLLER = 3;

    # ITEM FUNCTIONS

    public static function isSign(Item $item) : bool {

        $signs = [Item::SIGN, Item::BIRCH_SIGN, Item::SPRUCE_SIGN, Item::JUNGLE_SIGN, Item::DARKOAK_SIGN, Item::ACACIA_SIGN];

        $id = $item->getId();

        return self::arr_contains_value($id, $signs);
    }

    public static function getProperCount(int $count) : int {
        return ($count <= 0 ? 1 : $count);
    }

    public static function getItemFromString(String $s) {

        $itemArr = [];

        $enchantsArr = [];

        if(self::str_contains('-', $s)) {

            $arr = explode('-', $s);
            $arrSize = count($arr);
            $itemArr = explode(':', strval($arr[0]));

            if($arrSize > 1) $enchantsArr = explode(',', strval($arr[1]));

        } else $itemArr = explode(':', $s);

        $baseItem = null;

        $len = count($itemArr);

        if($len >= 1 and $len < 4) {

            $id = intval($itemArr[0]);
            $count = 1;
            $meta = 0;

            if($len == 2) $meta = intval($itemArr[1]);
            else if($len == 3){
                $count = intval($itemArr[2]);
                $meta = intval($itemArr[1]);
            }

            $isGoldenHead = false;

            if($id === Item::GOLDEN_APPLE and $meta === 1) {
                $isGoldenHead = true;
                $meta = 0;
            }

            $baseItem = Item::get($id, $meta, $count);

            if ($isGoldenHead === true) $baseItem = $baseItem->setCustomName(self::getName('golden-head'));
        }

        $enchantCount = count($enchantsArr);

        if($enchantCount > 0 and !is_null($baseItem)) {
            for ($i = 0; $i < $enchantCount; $i++) {
                $enchant = strval($enchantsArr[$i]);
                $enArr = explode(':', $enchant);
                $arrCount = count($enArr);
                if ($arrCount === 2) {
                    $eid = intval($enArr[0]);
                    $elvl = intval($enArr[1]);
                    $e = new EnchantmentInstance(Enchantment::getEnchantment($eid), $elvl);
                    $baseItem->addEnchantment($e);
                }
            }
        }

        return $baseItem;
    }

    public static function getArmorFromKey(string $s) : int {
        $result = -1;
        switch($s){
            case 'helmet':
                $result = 0;
                break;
            case 'chestplate':
                $result = 1;
                break;
            case 'leggings':
                $result = 2;
                break;
            case 'boots':
                $result = 3;
                break;
        }
        return $result;
    }
    
    public static function inventoryToArray(Player $player, bool $keepAir = false) : array {
        
        $result = [];

        $armor = [];
        $items = [];

        $armorInv = $player->getArmorInventory();
        $itemInv = $player->getInventory();

        $armorSize = $armorInv->getSize();

        $armorVals = ['helmet', 'chestplate', 'boots', 'leggings'];

        for($i = 0; $i < $armorSize; $i++){
            $item = $armorInv->getItem($i);
            if(isset($armorVals[$i])) {
                $key = $armorVals[$i];
                $armor[$key] = $item;
            }
        }

        $itemSize = $itemInv->getSize();

        for($i = 0; $i < $itemSize; $i++){
            $item = $itemInv->getItem($i);
            $exec = (!$keepAir and $item->getId() === 0) ? false : true;
            if($exec === true) $items[] = $item;
        }

        $result['armor'] = $armor;
        $result['items'] = $items;

        return $result;
    }

    public static function getBlockFromArr(array $arr) {

        $result = null;

        if(self::arr_contains_keys($arr, 'id', 'meta')) {
            $id = intval($arr['id']);
            $meta = intval($arr['meta']);

            $result = Block::get($id, $meta);
        }

        return $result;
    }

    public static function blockToArr(Block $block) : array {
        return ['id' => $block->getId(), 'meta' => $block->getDamage()];
    }

    //SERVER CONFIGURATION FUNCTIONS

    public static function getDefaultLevel() : Level {

        $server = Server::getInstance();

        $cfg = PracticeCore::getInstance()->getConfig();
        $level = strval($cfg->get('lobby-level'));
        $result = null;

        if(isset($level) and !is_null($level)){
            $lvl = $server->getLevelByName($level);
            if(!is_null($lvl))
                $result = $lvl;

        }

        if(is_null($result)) $result = $server->getDefaultLevel();
        return $result;
    }

    public static function isChatFilterEnabled() : bool {
        $cfg = PracticeCore::getInstance()->getConfig();
        return boolval($cfg->get('enable-chat-filter'));
    }

    public static function isLobbyProtectionEnabled() : bool {
        $cfg = PracticeCore::getInstance()->getConfig();
        return boolval($cfg->get('lobby-protection'));
    }

    public static function isEnderpearlCooldownEnabled() : bool {
        $cfg = PracticeCore::getInstance()->getConfig();
        return boolval($cfg->get('enable-enderpearl-cooldown'));
    }

    public static function isTapToPearlEnabled() : bool {
        $cfg = PracticeCore::getInstance()->getConfig();
        return boolval($cfg->get('enable-tap-to-pearl'));
    }

    public static function isTapToPotEnabled() : bool {
        $cfg = PracticeCore::getInstance()->getConfig();
        return boolval($cfg->get('enable-tap-to-pot'));
    }

    public static function isTapToRodEnabled() : bool {
        $cfg = PracticeCore::getInstance()->getConfig();
        return boolval($cfg->get('enable-tap-to-rod'));
    }

    public static function isRanksEnabled() : bool {
        $cfg = PracticeCore::getInstance()->getConfig();
        return boolval($cfg->get('enable-ranks'));
    }

    public static function setRanksEnabled(bool $res) : void {
        $cfg = PracticeCore::getInstance()->getConfig();
        $cfg->set('enable-ranks', $res);
        $cfg->save();
    }

    public static function isItemFormsEnabled() : bool {
        $cfg = PracticeCore::getInstance()->getConfig();
        return boolval($cfg->get('enable-hub-formwindows'));
    }

    public static function setItemFormsEnabled(bool $res = true) : void {
        $cfg = PracticeCore::getInstance()->getConfig();
        $cfg->set('enable-hub-formwindows', $res);
        $cfg->save();
    }

    //TIME FUNCTIONS

    public static function currentTimeMillis() : float {
        $time = microtime(true);
        return round($time * 1000);
    }

    public static function secondsToTicks(int $seconds) : int {
        return $seconds * 20;
    }

    public static function minutesToTicks(int $minutes) : int {
        return $minutes * 1200;
    }

    public static function hoursToTicks(int $hours) : int {
        return $hours * 72000;
    }

    public static function ticksToSeconds(int $tick) : int {
        return intval($tick / 20);
    }

    public static function ticksToMinutes(int $tick) : int {
        return intval($tick / 1200);
    }

    public static function ticksToHours(int $tick) : int {
        return intval($tick / 72000);
    }

    public static function getLastDayOfMonth(int $month, int $year) : int {

        $result = -1;

        $thirtyOne = [1, 3, 5, 7, 8, 10, 12];

        $thirty = [4, 6, 9, 11];

        $feb = 2;

        if(self::arr_contains_value($month, $thirtyOne)) $result = 31;
        elseif (self::arr_contains_value($month, $thirty)) $result = 30;
        elseif ($month === $feb) $result = intlgregcal_is_leap_year($year) ? 29 : 28;

        return $result;
    }

    //PLAYER SPECIFIC FUNCTIONS

    public static function testPermission(CommandSender $sender, string $permission, bool $sendMsg = true) : bool {

        $msg = null;

        $result = true;

        if($sender instanceof Player) {
            $result = PracticeCore::getPermissionHandler()->testPermission($permission, $sender->getPlayer());
            if($result === false and $sendMsg === true) $msg = self::getMessage("permission-msg");
        }

        if(!is_null($msg)) $sender->sendMessage($msg);

        return $result;
    }

    public static function genAnonymousName() : string {
        $result = 'anonymous';
        $val = rand(0, 100000);
        return $result . $val;
    }

    public static function getPlayerName($player) {
        $result = null;
        if(isset($player) and !is_null($player)) {
            if($player instanceof Player) {
                $result = $player->getName();
            } elseif ($player instanceof PracticePlayer){
                $result = $player->getPlayerName();
            } elseif (is_string($player)) {
                $result = $player;
            }
        }
        return $result;
    }

    public static function isInSpectatorMode($player) : bool {

        $result = false;

        $playerHandler = PracticeCore::getPlayerHandler();

        if($playerHandler->isPlayer($player)) {
            $p = $playerHandler->getPlayer($player);
            if($p->getPlayer()->getGamemode() === 3) {
                $result = true;
            } else $result = $p->isInvisible() and self::canFly($p->getPlayer()) and !$p->canHitPlayer();


        }
        return $result;
    }

    public static function setInSpectatorMode($player, bool $spec = true, bool $forDuels = false) : void {

        $playerHandler = PracticeCore::getPlayerHandler();

        if($playerHandler->isPlayer($player)) {

            $p = $playerHandler->getPlayer($player);

            if($spec === true){
                if(!$forDuels) {
                    $p->getPlayer()->setGamemode(3);
                } else {
                    $p->setCanHitPlayer(false);
                    $p->setInvisible(true);
                    self::setCanFly($player, true);
                }
            } else {
                $p->getPlayer()->setGamemode(0);
                $p->setCanHitPlayer(true);
                $p->setInvisible(false);
                self::setCanFly($player, false);
            }
        }
    }

    public static function setFrozen(Player $player, bool $freeze, bool $forDuels = false) : void {

        if(!is_null($player) and $player->isOnline()){
            $player->setImmobile($freeze);
            if($forDuels === false){
                $msg = ($freeze === true) ? self::getMessage('frozen.active') : self::getMessage('frozen.inactive');
                $player->sendMessage($msg);
            }
        }
    }

    public static function isFrozen(Player $player) : bool {
        return $player->isImmobile();
    }

    public static function canFly(Player $player) : bool {
        return $player->getAllowFlight();
    }

    public static function setCanFly($player, bool $res) : void {

        $pl = null;

        if (isset($player) and !is_null($player)) {

            if ($player instanceof Player)
                $pl = $player;

            elseif ($player instanceof PracticePlayer)

                if ($player->isOnline())
                    $pl = $player->getPlayer();

            else if (is_string($player))
                $pl = Server::getInstance()->getPlayer($player);
        }

        if (!is_null($pl)) {

            $pl->setAllowFlight($res);

            if($res === false and $pl->isFlying()){
                $pl->setFlying(false);
            }
        }
    }

    public static function setCanHit($player, bool $res) : void {

        $pl = null;

        if (isset($player) and !is_null($player)) {
            if ($player instanceof Player) {
                $pl = $player;
            } elseif ($player instanceof PracticePlayer) {
                if ($player->isOnline())
                    $pl = $player->getPlayer();
            } else if (is_string($player)) {
                $pl = Server::getInstance()->getPlayer($player);
            }
        }

        if(!is_null($pl)){
            $pkt = new AdventureSettingsPacket();
            $pkt->setFlag(AdventureSettingsPacket::NO_PVP, $res);
            $pkt->entityUniqueId = $pl->getId();

            if($pl->handleAdventureSettings($pkt))
                $pl->dataPacket($pkt);

        }
    }

    public static function canUseItems($player, bool $lobby = false) : bool {

        $result = false;

        $playerHandler = PracticeCore::getPlayerHandler();

        $duelHandler = PracticeCore::getDuelHandler();

        if($playerHandler->isPlayerOnline($player)) {

            $p = $playerHandler->getPlayer($player);

            $pl = $p->getPlayer();

            $level = $pl->getLevel();

            $execute = false;

            if(self::isLobbyProtectionEnabled()) {

                if($lobby === false) {

                    if(!self::areLevelsEqual($level, self::getDefaultLevel()))
                        $execute = true;
                    else {
                        if($p->isInArena()) $execute = true;
                        elseif ($p->isInDuel()) {
                            $duel = $duelHandler->getDuel($pl);
                            if(!$duel->isLoadingDuel()) $execute = true;
                        }
                    }
                } else $execute = self::areLevelsEqual($level, self::getDefaultLevel()) and !$p->isInDuel() and !$p->isInArena();

            } else $execute = true;


            if($execute === true) {

                $test = !$p->isInvisible() and !self::isFrozen($p->getPlayer());

                $result = ($test === true) ? true : $duelHandler->isASpectator($p->getPlayer());
            }
        }
        return $result;
    }

    public static function throwPotion(SplashPotion $potion, $player, bool $animate = false) {

        $playerHandler = PracticeCore::getPlayerHandler();

        if($playerHandler->isPlayerOnline($player)) {
            $p = $playerHandler->getPlayer($player);
            $pl = $p->getPlayer();
            $potion->onClickAir($pl, $pl->getDirectionVector());
            if(!$pl->isCreative()) {
                $inv = $pl->getInventory();
                $inv->setItem($inv->getHeldItemIndex(), Item::get(0));
            }
            if($animate) {
                $pkt = new AnimatePacket();
                $pkt->action = AnimatePacket::ACTION_SWING_ARM;
                $pkt->entityRuntimeId = $pl->getId();
                Server::getInstance()->broadcastPacket($pl->getLevel()->getPlayers(), $pkt);
            }
        }
    }

    public static function throwPearl(EnderPearl $item, $player, bool $animate = false) {

        $exec = false;

        $playerHandler = PracticeCore::getPlayerHandler();

        if($playerHandler->isPlayerOnline($player)) {

            $p = $playerHandler->getPlayer($player);

            $exec = self::isEnderpearlCooldownEnabled() ? $p->canThrowPearl() : true;
        }

        if($exec) {

            $p = $playerHandler->getPlayer($player);
            $pl = $p->getPlayer();
            $item->onClickAir($pl, $pl->getDirectionVector());

            if(self::isEnderpearlCooldownEnabled())
                $p->setThrowPearl(false);

            if($animate) {
                $pkt = new AnimatePacket();
                $pkt->action = AnimatePacket::ACTION_SWING_ARM;
                $pkt->entityRuntimeId = $pl->getId();
                Server::getInstance()->broadcastPacket($pl->getLevel()->getPlayers(), $pkt);
            }

            if(!$pl->isCreative()) {
                $inv = $pl->getInventory();
                $index = $inv->getHeldItemIndex();
                $count = $item->getCount();
                if($count > 1) $inv->setItem($index, Item::get($item->getId(), $item->getDamage(), $count));
                else $inv->setItem($index, Item::get(0));
            }
        }
    }

    public static function useRod(Item $item, $player, bool $animate = false) {

        $exec = false;

        $playerHandler = PracticeCore::getPlayerHandler();

        if($playerHandler->isPlayerOnline($player)) {

            $p = $playerHandler->getPlayer($player);

            $pl = $p->getPlayer();

            $players = $pl->getLevel()->getPlayers();

            if($p->isFishing()) {

                $p->stopFishing();
                $exec = true;

                if($animate === true) {
                    $pkt = new AnimatePacket();
                    $pkt->action = AnimatePacket::ACTION_SWING_ARM;
                    $pkt->entityRuntimeId = $pl->getId();
                    Server::getInstance()->broadcastPacket($players, $pkt);
                }

            } else {

                $p->startFishing();

                if($animate === true) {
                    $pkt = new AnimatePacket();
                    $pkt->action = AnimatePacket::ACTION_SWING_ARM;
                    $pkt->entityRuntimeId = $pl->getId();
                    Server::getInstance()->broadcastPacket($players, $pkt);
                }
            }
        }

        if($exec === true) {
            $practicePlayer = $playerHandler->getPlayer($player);
            $p = $practicePlayer->getPlayer();
            $inv = $p->getInventory();
            if(!$p->isCreative()) {
                $newItem = Item::get($item->getId(), $item->getDamage() + 1);
                if($item->getDamage() > 65)
                    $newItem = Item::get(0);
                $inv->setItemInHand($newItem);
            }
        }
    }

    public static function checkActions(int $action, int...$actions) : bool {
        return self::arr_indexOf($action, $actions, true) !== -1;
    }

    public static function canPlayerChat(Player $p) : bool {
        $res = true;
        $playerHandler = PracticeCore::getPlayerHandler();
        if(PracticeCore::getInstance()->isServerMuted())
            $result = !$playerHandler->isOwner($p) and !$playerHandler->isMod($p) and !$playerHandler->isAdmin($p);
        else
            $res = !$playerHandler->isPlayerMuted($p->getName());

        return $res;
    }

    public static function canRequestPlayer(Player $sender, $player) : bool {

        $result = false;

        $playerHandler = PracticeCore::getPlayerHandler();

        if($playerHandler->isPlayerOnline($player)) {
            $msg = null;
            $requested = $playerHandler->getPlayer($player);
            $rqName = $requested->getPlayerName();

            if($requested->isInArena())
                $msg = self::str_replace(self::getMessage('duels.misc.arena-msg'), ['%player%' => $rqName]);
            else {
                if(PracticeCore::getDuelHandler()->isWaitingForDuelToStart($rqName) or $requested->isInDuel()) {
                    $msg = self::str_replace(self::getMessage('duels.misc.in-duel'), ['%player%' => $rqName]);
                } else {
                    if($requested->canSendDuelRequest())
                        $result = true;
                    else {
                        $sec = self::ticksToSeconds($requested->getCantSpamDuelTicks());
                        $msg = self::str_replace(self::getMessage('duels.misc.anti-spam'), ['%player%' => $rqName, '%time%' => "$sec"]);
                    }
                }
            }

            if(!is_null($msg)) $sender->sendMessage($msg);
        }
        return $result;
    }

    public static function canAcceptPlayer(Player $sender, $player) : bool {

        $result = false;

        $ivsiHandler = PracticeCore::get1vs1Handler();

        if(self::canRequestPlayer($sender, $player)) {
            if($ivsiHandler->hasPendingRequest($sender, $player)) {
                $request = $ivsiHandler->getRequest($sender, $player);
                $result = $request->canAccept();
            } else self::getMessage('duels.1vs1.no-pending-rqs');
        }

        return $result;
    }

    public static function canExecAcceptCommand(Player $player, string $permission) : bool {
        return self::canExecDuelCommand($player, $permission, true);
    }

    public static function canExecSpecCommand(Player $player, string $permission) : bool {

        $msg = null;
        $result = false;

        $playerHandler = PracticeCore::getPlayerHandler();

        if($playerHandler->isPlayerOnline($player)) {

            $p = $playerHandler->getPlayer($player);

            if(self::testPermission($player, $permission)) {

                if($p->canUseCommands(true)) {

                    if(!$p->isInArena()){

                        if(!$p->isInDuel() and !PracticeCore::getDuelHandler()->isWaitingForDuelToStart($p)) {

                            $result = true;

                            if($result === true and self::isInSpectatorMode($p->getPlayer())) {
                                $result = false;
                                $msg = self::getMessage('spectator-mode-message');
                            }

                        } else $msg = self::getMessage('duels.misc.fail-match');

                    } else $msg = self::getMessage('duels.misc.fail-arena');
                }
            }
        }

        if(!is_null($msg)) $player->sendMessage($msg);

        return $result;
    }

    public static function canExecDuelCommand(Player $player, string $permission, bool $isRequesting = false) : bool {

        $msg = null;
        $result = false;

        $playerHandler = PracticeCore::getPlayerHandler();

        if($playerHandler->isPlayerOnline($player)) {

            $p = $playerHandler->getPlayer($player);

            if(self::testPermission($player, $permission)) {

                if ($p->canUseCommands(true)) {

                    if (!$p->isInArena()) {

                        $duelHandler = PracticeCore::getDuelHandler();

                        if (!$p->isInDuel() and !$duelHandler->isWaitingForDuelToStart($p)) {

                            $exec = false;

                            if ($isRequesting) $exec = true;

                            else {
                                if (!$duelHandler->isPlayerInQueue($p))
                                    $exec = true;
                                else $msg = self::getMessage('duels.misc.fail-queue');
                            }

                            $result = $exec;

                            if ($result === true and self::isInSpectatorMode($p->getPlayer())) {
                                $result = false;
                                $msg = self::getMessage('spectator-mode-message');
                            }

                        } else $msg = self::getMessage('duels.misc.fail-match');

                    } else $msg = self::getMessage('duels.misc.fail-arena');

                }
            }
        }

        if(!is_null($msg)) $player->sendMessage($msg);
        return $result;
    }

    public static function canExecBasicCommand(CommandSender $sender, bool $consoleRunCommand = true, bool $canRunInSpec = false) : bool {

        $exec = false;
        $msg = null;

        $playerHandler = PracticeCore::getPlayerHandler();

        if($sender instanceof Player){

            $pl = $sender->getPlayer();

            if($playerHandler->isPlayer($pl)){

                $p = $playerHandler->getPlayer($pl);
                $exec = true;

                if($p->canUseCommands(true)) {
                    if(self::isInSpectatorMode($pl)) {
                        $exec = $canRunInSpec;
                        if ($canRunInSpec === false) $msg = self::getMessage('spectator-mode-msg');
                    }

                }
            } else $exec = false;

        } else {

            $exec = $consoleRunCommand;

            if($exec === false)
                $msg = self::getMessage('console-usage-command');
        }

        if(!is_null($msg)) $sender->sendMessage($msg);

        return $exec;
    }

    public static function canExecutePartyCmd(CommandSender $sender, string $command = 'help') : bool {

        $result = false;

        $msg = null;

        $playerHandler = PracticeCore::getPlayerHandler();

        $name = $sender->getName();

        if($sender instanceof Player and $playerHandler->isPlayerOnline($name)) {

            $p = $playerHandler->getPlayer($name);

            if($p->canUseCommands(true)) {

                if($p->isInArena()) $msg = self::getMessage("party.general.fail-lobby");

                else {

                    if(!$p->isInParty()) {

                        $invalidCmds = ['invite' => true, 'kick' => true, 'leave' => true];

                        if(array_key_exists($command, $invalidCmds))
                            $msg = self::getMessage('party.general.fail.no-party');

                        else $result = true;

                    } else {

                        $name = $p->getPlayerName();

                        if (PracticeCore::getPartyManager()->isLeaderOFAParty($name)){
                            $result = $command !== 'create';
                            if($result === false)
                                $msg = self::getMessage('party.create.fail-leader');

                        } else {

                            $invalidCmds = ['create' => true, 'invite' => true, 'kick' => true, 'accept'];

                            if(array_key_exists($command, $invalidCmds))
                                $msg = ($command === 'create') ? self::getMessage('party.create.fail-leave') : ($command === 'accept') ? self::getMessage('party.accept.in-party') : self::getMessage('party.general.fail-manager');
                            else $result = true;
                        }
                    }
                }
            }
        } else $msg = self::getMessage('console-usage-command');

        if(!is_null($msg)) $sender->sendMessage($msg);

        return $result;
    }

    public static function transferEveryone() : void {

        $server = Server::getInstance();

        $players = $server->getOnlinePlayers();

        $serverIP = Internet::getIP();

        echo $serverIP . "\n";

        $serverPort = $server->getPort();

        foreach($players as $player)
            $player->transfer($serverIP, $serverPort, "Reloading Server");
    }

    public static function getSpawnPosition() : Position {
        $lvl = self::getDefaultLevel();
        if(is_null($lvl)) $lvl = Server::getInstance()->getDefaultLevel();
        $spawnPos = $lvl->getSpawnLocation();
        if(is_null($spawnPos)) $spawnPos = $lvl->getSafeSpawn();
        return $spawnPos;
    }

    public static function respawnPlayer(Player $player, bool $clearInv = true, bool $reset = false) : void {

        $playerHandler = PracticeCore::getPlayerHandler();

        if($reset === true)
            self::resetPlayer($player, $clearInv, false);

        else {

            if(self::isFrozen($player)) self::setFrozen($player, false);

            if(self::isInSpectatorMode($player)){

                self::setInSpectatorMode($player, false);

            } else {

                if (self::canFly($player)) { self::setCanFly($player, false); }

                if ($playerHandler->isPlayerOnline($player)) {
                    $p = $playerHandler->getPlayer($player);
                    $p->setCanHitPlayer(false);
                    if ($p->isInvisible()) { $p->setInvisible(false); }
                }
            }

            if($playerHandler->isPlayerOnline($player)) {

                $p = $playerHandler->getPlayer($player);

                if($p->isInArena()) $p->setCurrentArena(PracticeArena::NO_ARENA);

                if(!$p->canThrowPearl()) $p->setThrowPearl(true);
                if($p->isInCombat()) $p->setInCombat(false);

                $p->setScoreboard(Scoreboard::SPAWN_SCOREBOARD);
            }

            ScoreboardUtil::updateSpawnScoreboards();

            PracticeCore::getItemHandler()->spawnHubItems($player, $clearInv);
        }
    }

    public static function resetPlayer(Player $player, bool $clearInv = true, bool $teleport = true) : void {

        $playerHandler = PracticeCore::getPlayerHandler();

        if(!is_null($player) and $player->isOnline()) {

            if($player->getGamemode() !== 0) $player->setGamemode(0);

            if($player->hasEffects()) $player->removeAllEffects();

            if($player->getHealth() !== $player->getMaxHealth()) $player->setHealth($player->getMaxHealth());

            if($teleport === true) $player->teleport(self::getSpawnPosition());

            if($player->isOnFire()) $player->extinguish();

            if(self::isFrozen($player)) self::setFrozen($player, false);

            if(self::isInSpectatorMode($player)){

                self::setInSpectatorMode($player, false);

            } else {

                if (self::canFly($player)) { self::setCanFly($player, false); }

                if ($playerHandler->isPlayerOnline($player)) {
                    $p = $playerHandler->getPlayer($player);
                    $p->setCanHitPlayer(false);
                    if ($p->isInvisible()) { $p->setInvisible(false); }
                }
            }

            if($playerHandler->isPlayerOnline($player)) {

                $p = $playerHandler->getPlayer($player);

                if($p->isInArena()) $p->setCurrentArena(PracticeArena::NO_ARENA);

                if(!$p->canThrowPearl()) $p->setThrowPearl(true);
                if($p->isInCombat()) $p->setInCombat(false);

                $p->setScoreboard(Scoreboard::SPAWN_SCOREBOARD);
            }

            ScoreboardUtil::updateSpawnScoreboards();

            PracticeCore::getItemHandler()->spawnHubItems($player, $clearInv);
        }
    }

    /**
     * @param int $id
     * @return Player|null
     */
    public static function getPlayerByID(int $id) {
        $result = null;

        $online = Server::getInstance()->getOnlinePlayers();

        foreach($online as $player) {
            $theID = $player->getId();
            if($id === $theID) {
                $result = $player;
                break;
            }
        }
        return $result;
    }

    public static function isPlayer(int $id) : bool {
        return !is_null(self::getPlayerByID($id));
    }

    public static function kill($player) : void {

        $playerHandler = PracticeCore::getPlayerHandler();

        if($playerHandler->isPlayer($player)) {

            $p = $playerHandler->getPlayer($player);

            $pl = $p->getPlayer();

            if($p->isOnline()) {

                $ev = $pl->getLastDamageCause();
                if($ev === null) $ev = new EntityDamageEvent($pl, EntityDamageEvent::CAUSE_CUSTOM, 1000);

                $found = false;

                if($ev instanceof EntityDamageByEntityEvent) {
                    $dmgr = $ev->getDamager();
                    if($dmgr instanceof Player and $playerHandler->isPlayer($dmgr->getName())) {
                        $name = $dmgr->getName();
                        $attacker = PracticeCore::getPlayerHandler()->getPlayer($name);
                        $differenceTicks = $attacker->getCurrentTick() - $attacker->getLastTickInCombat();
                        $seconds = self::ticksToSeconds($differenceTicks);
                        if($seconds <= 20) $found = true;
                    }
                }

                if($found === true) $ev = new EntityDamageEvent($pl, EntityDamageEvent::CAUSE_CUSTOM, 1000);

                $ev->call();
                $pl->setLastDamageCause($ev);
                $pl->setHealth(20);

            } else {

                if(!is_null($p)) {

                    $drops = $pl->getDrops();

                    $ev = new PlayerDeathEvent($pl, $drops);
                    $ev->setKeepInventory(false);
                    $ev->call();
                    $level = $pl->getLevel();

                    foreach($drops as $item) {
                        if($item instanceof Item) $level->dropItem($pl, $item);
                    }
                }
            }
        }
    }

    //TEXT/MESSAGE FUNCTIONS

    public static function broadcastMsg(string $msg) : void {

        $server = Server::getInstance();

        $players = $server->getOnlinePlayers();

        foreach($players as $player)
            $player->sendMessage($msg);

        $server->getLogger()->info($msg);
    }

    public static function getMessage(string $str) : string {

        $cfg = PracticeCore::getInstance()->getMessageConfig();

        $obj = '';

        if(self::str_contains('.', $str)){

            $arrSplit = explode('.', $str);

            $len = count($arrSplit);

            $val = $cfg->get($arrSplit[0]);

            for($i = 1; $i < $len; $i++)
                $val = $val[$arrSplit[$i]];


            $obj = strval($val);

        } else $obj = strval($cfg->get($str));

        return $obj;
    }

    public static function getName(string $str) : string {

        $cfg = PracticeCore::getInstance()->getNameConfig();

        $obj = '';

        if(self::str_contains('.', $str)){

            $arrSplit = explode('.', $str);

            $val = $cfg->get($arrSplit[0]);

            $len = count($arrSplit);

            for($i = 1; $i < $len; $i++)
                $val = $val[$arrSplit[$i]];


            $obj = strval($val);

        } else $obj = strval($cfg->get($str));

        return $obj;
    }

    public static function getRankFormatOf(string $rank) : string {
        $cfg = PracticeCore::getInstance()->getRankConfig();
        $obj = $cfg->get('format');
        return strval($obj[$rank]['rank']);
    }

    public static function getUnfilteredChat(string $msg) : string {
        return PracticeCore::getChatHandler()->getUncensoredMessage($msg);
    }

    public static function getChatFormat(Player $player, string $msg) : string {
        $name = self::getNameForChat($player);
        $formatted = PracticeCore::getRankHandler()->getFormattedRanksOf($player->getName());
        $messageFormat = strVal(PracticeCore::getInstance()->getRankConfig()->get('chat-format'));
        $uncolored = self::getUncoloredString($formatted);
        $len = strlen($uncolored);

        if($len === 0){
            $index = strpos($messageFormat, ']');
            if(is_int($index)){
                $replaced = substr($messageFormat, 0, $index + 1);
                $messageFormat = str_replace($replaced, '', $messageFormat);
            }
        } else $messageFormat = str_replace('%formatted-ranks%', $formatted, $messageFormat);

        $messageFormat = str_replace('%player-name%', $name, $messageFormat);
        return str_replace('%msg%', $msg, $messageFormat);
    }

    public static function getNameForChat(Player $player) : string {

        $cfg = PracticeCore::getInstance()->getRankConfig();

        $rankHandler = PracticeCore::getRankHandler();

        $ranks = ($rankHandler->hasRanks($player)) ? $rankHandler->getRanksOf($player) : [RankHandler::$GUEST];

        $firstRank = $ranks[0];

        $rank = ($firstRank instanceof Rank) ? $firstRank->getLocalizedName() : RankHandler::$GUEST->getLocalizedName();

        $obj = $cfg->get('format');
        $str = TextFormat::RESET . strVal($obj[$rank]['p-name']);
        $str = str_replace('%player%', $player->getName(), $str);
        return $str . TextFormat::RESET;
    }

    public static function getUncoloredString(string $str) : string {
        return TextFormat::clean($str);
    }

    public static function isLineSeparator(string $str) : bool {

        $result = true;
        $uncolored = self::getUncoloredString($str);

        $len = strlen($uncolored);

        for($i = 0; $i < $len; $i++) {
            $char = strval($uncolored[$i]);
            if($char !== '-') {
                $result = false;
                break;
            }
        }

        return $result;
    }

    public static function getLineSeparator(array $str, bool $visible = true) : string {

        $count = count($str);

        $len = 20;

        $keys = array_keys($str);

        if($count > 0) {

            $greatest = self::getUncoloredString(strval($str[$keys[0]]));

            foreach($keys as $key) {

                $current = self::getUncoloredString(strval($str[$key]));

                if(strlen($current) > strlen($greatest))
                    $greatest = $current;
            }

            $len = strlen($greatest);
        }

        if($len > self::MOBILE_SEPARATOR_LEN) $len = self::MOBILE_SEPARATOR_LEN;

        $str = '';
        $count = 0;

        while($count < $len) {

            $character = ($visible === true) ? '-' : ' ';

            $str .= $character;

            $count++;
        }

        return $str;
    }

    // LEVEL/POSITION FUNCTIONS

    public static function playerToLocation(Player $p) : Location {
        return new Location($p->x, $p->y, $p->z, $p->yaw, $p->pitch, $p->getLevel());
    }

    public static function clearEntitiesIn(Level $level) : void {

        $entities = $level->getEntities();

        foreach($entities as $entity) {

            $exec = true;

            if($entity instanceof Player) $exec = false;
            elseif ($entity instanceof FishingHook) $exec = false;
            elseif ($entity instanceof \pocketmine\entity\projectile\EnderPearl) $exec = false;
            elseif ($entity instanceof \pocketmine\entity\projectile\SplashPotion) $exec = false;

            if($exec === true)
                $entity->close();
        }
    }
    
    public static function roundPosition($pos) {

        $result = $pos;

        if($pos instanceof Position) {
            $result = new Position(intval(round($pos->x)), intval(round($pos->y)), intval(round($pos->z)), $pos->getLevel());
        } elseif ($pos instanceof Vector3) {
            $result = new Vector3(intval(round($pos->x)), intval(round($pos->y)), intval(round($pos->z)));
        } elseif ($pos instanceof Location) {
            $result = new Location(intval(round($pos->x)), intval(round($pos->y)), intval(round($pos->z)), intval(round($pos->yaw)), intval(round($pos->pitch)), $pos->getLevel());
        }

        return $result;
    }

    public static function absPosition($pos) {
        $result = $pos;
        if($pos instanceof Position) {
            $result = new Position(intval($pos->x), intval($pos->y), intval($pos->z), $pos->getLevel());
        } elseif ($pos instanceof Vector3) {
            $result = new Vector3(intval($pos->x), intval($pos->y), intval($pos->z));
        } elseif ($pos instanceof Location) {
            $result = new Location(intval($pos->x), intval($pos->y), intval($pos->z), $pos->yaw, $pos->pitch, $pos->getLevel());
        }
        return $result;
    }

    public static function isALevel($s) : bool {
        return is_string($s) and !is_null(Server::getInstance()->getLevelByName($s));
    }

    public static function areLevelsEqual(Level $a, Level $b) : bool {
        $aName = $a->getName();
        $bName = $b->getName();
        return $aName === $bName;
    }

    public static function getPositionToMap(Position $pos) : array {
        $result = [
            'x' => intval(round($pos->x)),
            'y' => intval(round($pos->y)),
            'z' => intval(round($pos->z)),
        ];

        if($pos instanceof Location) {
            $result['yaw'] = intval(round($pos->yaw));
            $result['pitch'] = intval(round($pos->pitch));
        }
        return $result;
    }
    
    public static function getPositionFromMap($posArr, $level) {
        $result = null;

        if(!is_null($posArr) and is_array($posArr) and self::arr_contains_keys($posArr,'x', 'y', 'z')) {

            $x = floatval(intval($posArr['x']));
            $y = floatval(intval($posArr['y']));
            $z = floatval(intval($posArr['z']));

            if(self::isALevel($level)) {

                $server = Server::getInstance();

                if(self::arr_contains_keys($posArr, 'yaw', 'pitch')) {
                    $yaw = floatval(intval($posArr['yaw']));
                    $pitch = floatval(intval($posArr['pitch']));
                    $result = new Location($x, $y, $z, $yaw, $pitch, $server->getLevelByName($level));
                } else
                    $result = new Position($x, $y, $z, $server->getLevelByName($level));

            }
        }

        return $result;
    }


    public static function arePositionsEqual($startPos1, $startPos2) : bool {

        $result = false;

        $lvl1 = null; $lvl2 = null;

        $pos1 = null; $pos2 = null;

        if($startPos1 instanceof Position) {
            $pos1 = $startPos1;
            $lvl1 = $pos1->getLevel();
        } elseif ($startPos1 instanceof Location) {
            $pos1 = new Position($startPos1->x, $startPos1->y, $startPos1->z, $startPos1->level);
            $lvl1 = $pos1->getLevel();
        } elseif ($startPos1 instanceof Vector3) {
            $pos1 = new Position($startPos1->x, $startPos1->y, $startPos1->z);
        }

        if($startPos2 instanceof Position) {
            $pos2 = $startPos2;
            $lvl2 = $pos2->getLevel();
        } elseif ($startPos1 instanceof Location) {
            $pos2 = new Position($startPos2->x, $startPos2->y, $startPos2->z, $startPos2->level);
            $lvl2 = $pos2->getLevel();
        } elseif ($startPos1 instanceof Vector3) {
            $pos2 = new Position($startPos2->x, $startPos2->y, $startPos2->z);
        }

        if($pos1 !== null and $pos2 !== null) {

            $x1 = $pos1->x;
            $x2 = $pos2->x;
            $y1 = $pos1->y;
            $y2 = $pos2->y;
            $z1 = $pos1->z;
            $z2 = $pos2->z;

            if ($x1 === $x2 and $y1 === $y2 and $z1 === $z2) {

                if($lvl1 !== null and $lvl2 !== null)
                    $result = self::areLevelsEqual($lvl1, $lvl2);

                else $result = true;
            }
        }

        return $result;
    }

    //BLOCK FUNCTIONS

    public static function isGravityBlock($block) : bool {
        $result = false;
        if(is_int($block)) {
            $result = $block === Block::SAND or $block === Block::GRAVEL;
        } elseif ($block instanceof Block) {
            $result = $block->getId() === Block::SAND or $block === Block::GRAVEL;
        }
        return $result;
    }

    //SERVER FUNCTIONS

    public static function kickAll(string $msg) : void {

        $players = Server::getInstance()->getOnlinePlayers();

        foreach($players as $player)
            $player->kick($msg);
    }

    public static function reloadPlayers() : void {

        $players = Server::getInstance()->getOnlinePlayers();
        $playerSize = count($players);

        if($playerSize > 0) {

            $playerHandler = PracticeCore::getPlayerHandler();

            foreach($players as $p) {

                $pl = $playerHandler->addPlayer($p);

                if(!is_null($pl) and $playerHandler->hasPendingPInfo($p)) {
                    $plInfo = $playerHandler->getPendingPInfo($p);
                    $device = intval($plInfo['device']);
                    $input = intval($plInfo['controls']);
                    $pl->setDeviceOS($device);
                    $pl->setInput($input);
                    $playerHandler->removePendingPInfo($p);
                }
                self::resetPlayer($p, true);
            }
        }
    }

    // USEFUL FUNCTIONS

    public static function str_indexOf(string $needle, string $haystack, int $len = 0) : int {

        $result = -1;

        $indexes = self::str_indexes($needle, $haystack);

        $length = count($indexes);

        if($length > 0) {

            $length = $length - 1;

            $indexOfArr = ($len > $length or $len < 0 ? 0 : $len);

            $result = $indexes[$indexOfArr];

        }

        return $result;
    }

    /**
     * @param string $needle
     * @param string $haystack
     * @return array|int[]
     */
    public static function str_indexes(string $needle, string $haystack) : array {

        $result = [];

        $end = strlen($needle);

        $len = 0;

        while (($len + $end) <= strlen($haystack)) {

            $substr = substr($haystack, $len, $end);

            if ($needle === $substr)
                $result[] = $len;

            $len++;
        }

        return $result;
    }

    public static function str_contains_vals(string $haystack, string...$needles) : bool {

        $result = true;

        $size = count($needles);

        if($size > 0) {
            foreach ($needles as $needle) {
                if(!self::str_contains($needle, $haystack)) {
                    $result = false;
                    break;
                }
            }
        } else $result = false;


        return $result;
    }

    public static function str_contains_from_arr(string $haystack, array $needles) : bool {

        $result = true;

        $size = count($needles);

        if($size > 0) {
            foreach($needles as $needle) {
                if(!self::str_contains($needle, $haystack)) {
                    $result = false;
                    break;
                }
            }
        } else $result = false;

        return $result;
    }
    
    public static function str_contains(string $needle, string $haystack, bool $use_mb = false) : bool {
        $result = false;
        $type = ($use_mb === true) ? mb_strpos($haystack, $needle) : strpos($haystack, $needle);
        if(is_bool($type)){
            $result = $type;
        } elseif (is_int($type)){
            $result = $type > -1;
        }
        return $result;
    }

    public static function arr_indexOf($needle, array $haystack, bool $strict = false) {

        $index = array_search($needle, $haystack, $strict);

        if(is_bool($index) and $index === false)
            $index = -1;

        return $index;
    }

    public static function arr_contains_keys(array $haystack, ...$needles) : bool {
        $result = true;

        foreach($needles as $key) {
            if(!array_key_exists($key, $haystack)) {
                $result = false;
                break;
            }
        }

        return $result;
    }

    public static function arr_contains_value($needle, array $haystack, bool $strict = TRUE) : bool {
        return in_array($needle, $haystack, $strict);
    }

    public static function arr_replace_values(array $arr, array $values) : array {

        $valuesKeys = array_keys($values);

        foreach($valuesKeys as $key) {

            $value = $values[$key];

            if(self::arr_contains_value($key, $arr)) {

                $keys = array_keys($arr);

                foreach ($keys as $editedArrKey) {
                    $origVal = $arr[$editedArrKey];
                    if ($origVal === $key)
                        $arr[$editedArrKey] = $value;

                }
            }
        }

        return $arr;
    }

    public static function equals_string(string $input, string...$tests) : bool {

        $result = false;

        foreach($tests as $test) {
            if($test === $input) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    public static function str_replace(string $haystack, array $values) : string {

        $result = $haystack;

        $keys = array_keys($values);

        foreach($keys as $value) {
            $value = strval($value);
            $replaced = strval($values[$value]);
            if(self::str_contains($value, $haystack)) {
                $result = str_replace($value, $replaced, $result);
            }
        }

        return $result;
    }

    public static function sort_array(array $arr) : array {
        if(count($arr) === 1)
            return $arr;
        $middle = intval(count($arr) / 2);
        $left = array_slice($arr, 0, $middle, true);
        $right = array_slice($arr, $middle, null, true);
        $left = self::sort_array($left);
        $right = self::sort_array($right);
        return self::merge($left, $right);
    }

    private static function merge(array $arr1, array $arr2) : array {

        $result = [];

        while(count($arr1) > 0 and count($arr2) > 0) {
            $leftKey = array_keys($arr1)[0];
            $rightKey = array_keys($arr2)[0];
            $leftVal = $arr1[$leftKey];
            $rightVal = $arr2[$rightKey];
            if($leftVal > $rightVal) {
                $result[$rightKey] = $rightVal;
                $arr2 = array_slice($arr2, 1, null, true);
            } else {
                $result[$leftKey] = $leftVal;
                $arr1 = array_slice($arr1, 1, null, true);
            }
        }

        while(count($arr1) > 0) {
            $leftKey = array_keys($arr1)[0];
            $leftVal = $arr1[$leftKey];
            $result[$leftKey] = $leftVal;
            $arr1 = array_slice($arr2, 1, null, true);
        }

        while(count($arr2) > 0) {
            $rightKey = array_keys($arr2)[0];
            $rightVal = $arr2[$rightKey];
            $result[$rightKey] = $rightVal;
            $arr2 = array_slice($arr2, 1, null, true);
        }

        return $result;
    }



    public static function canParse($s, bool $isInteger) : bool {

        $canParse = true;

        if(is_string($s)) {

            $abc = 'ABCDEFGHIJKLMNOPQRZTUVWXYZ';
            $invalid = $abc . strtoupper($abc) . "!@#$%^&*()_+={}[]|:;\"',<>?/";

            if ($isInteger === true) $invalid = $invalid . '.';

            $strArr = str_split($invalid);

            $canParse = self::str_contains_from_arr($s, $strArr);

        } else $canParse = ($isInteger === true) ? is_int($s) : is_float($s);

        return $canParse;
    }
}