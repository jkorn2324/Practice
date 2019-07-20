<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-18
 * Time: 09:17
 */

declare(strict_types=1);

namespace practice;

use pocketmine\block\Liquid;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\plugin\PluginDisableEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Bucket;
use pocketmine\item\EnderPearl;
use pocketmine\item\FlintSteel;
use pocketmine\item\Food;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\item\MushroomStew;
use pocketmine\item\Potion;
use pocketmine\item\SplashPotion;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use practice\anticheat\AntiCheatUtil;
use practice\arenas\PracticeArena;
use practice\game\FormUtil;
use practice\game\inventory\InventoryUtil;
use practice\game\inventory\menus\inventories\PracBaseInv;
use practice\game\items\PracticeItem;
use practice\player\permissions\PermissionsHandler;
use practice\player\PlayerSpawnTask;
use practice\player\RespawnTask;
use practice\scoreboard\ScoreboardUtil;
use practice\scoreboard\UpdateScoreboardTask;

class PracticeListener implements Listener
{
    private $core;

    public function __construct(PracticeCore $c) {
        $this->core = $c;
    }

    /*private function getCore(): PracticeCore
    {
        return $this->core;
    }*/

    public function onJoin(PlayerJoinEvent $event): void {

        $p = $event->getPlayer();

        $playerHandler = PracticeCore::getPlayerHandler();

        if (!is_null($p)) {

            $pl = $playerHandler->addPlayer($p);

            $nameTag = PracticeUtil::getNameTagFormat($p);

            $p->setNameTag($nameTag);

            $this->core->getScheduler()->scheduleDelayedTask(new PlayerSpawnTask($pl), 10);

            $event->setJoinMessage(PracticeUtil::str_replace(PracticeUtil::getMessage('join-msg'), ['%player%' => $p->getName()]));
        }
    }

    public function onLogin(PlayerLoginEvent $event) : void {

        $p = $event->getPlayer();

        if ($p->getGamemode() !== 0) $p->setGamemode(0);

        if ($p->hasEffects()) $p->removeAllEffects();

        $maxHealth = $p->getMaxHealth();

        if ($p->getHealth() !== $maxHealth) $p->setHealth($maxHealth);

        if ($p->isOnFire()) $p->extinguish();

        if (PracticeUtil::isFrozen($p)) PracticeUtil::setFrozen($p, false);

        if (PracticeUtil::isInSpectatorMode($p))
            PracticeUtil::setInSpectatorMode($p, false);

        $p->teleport(PracticeUtil::getSpawnPosition());
    }

    public function onLeave(PlayerQuitEvent $event): void {

        $p = $event->getPlayer();

        $playerHandler = PracticeCore::getPlayerHandler();

        $duelHandler = PracticeCore::getDuelHandler();

        if (!is_null($p) and $playerHandler->isPlayer($p)) {

            $pracPlayer = $playerHandler->getPlayer($p);

            if($pracPlayer->isFishing()) $pracPlayer->stopFishing(false);

            if($pracPlayer->isInParty()) {
                $party = PracticeCore::getPartyManager()->getPartyFromPlayer($pracPlayer->getPlayerName());
                $party->removeFromParty($pracPlayer->getPlayerName());
            }

            if($duelHandler->isPlayerInQueue($p))
                $duelHandler->removePlayerFromQueue($p);

            if ($pracPlayer->isInCombat()) PracticeUtil::kill($p);

            $playerHandler->removePlayer($p);

            $msg = PracticeUtil::str_replace(PracticeUtil::getMessage('leave-msg'), ['%player%' => $p->getName()]);

            $event->setQuitMessage($msg);

            $this->core->getScheduler()->scheduleDelayedTask(new UpdateScoreboardTask($pracPlayer), 1);
        }
    }

    public function onDeath(PlayerDeathEvent $event): void {

        $p = $event->getPlayer();

        $playerHandler = PracticeCore::getPlayerHandler();

        $level = $p->getLevel();

        $duelHandler = PracticeCore::getDuelHandler();

        if($playerHandler->isPlayerOnline($p)) {

            $player = $playerHandler->getPlayer($p);

            if($player->isFishing()) $player->stopFishing(false);

            $lastDamageCause = $p->getLastDamageCause();
            $addToStats = $player->isInArena() and ($player->getCurrentArenaType() === PracticeArena::FFA_ARENA);

            $diedFairly = true;

            if($lastDamageCause != null) {

                if ($lastDamageCause->getCause() === EntityDamageEvent::CAUSE_VOID) {
                    $diedFairly = false;
                    //if($player->isInDuel())
                } elseif ($lastDamageCause->getCause() === EntityDamageEvent::CAUSE_SUICIDE) {
                    $diedFairly = false;
                } elseif ($lastDamageCause->getCause() === EntityDamageEvent::CAUSE_SUFFOCATION) {
                    if ($p->isInsideOfSolid()) {
                        $pos = $p->getPosition();
                        $block = $level->getBlock($pos);
                        if (PracticeUtil::isGravityBlock($block))
                            $diedFairly = false;
                    }
                }
            }

            if($addToStats === true) {

                if($diedFairly === true) {

                    if($lastDamageCause instanceof EntityDamageByEntityEvent) {

                        $damgr = $lastDamageCause->getDamager();

                        if($playerHandler->isPlayerOnline($damgr)) {

                            $attacker = $playerHandler->getPlayer($damgr);

                            $p = $attacker->getPlayer();

                            if(!$attacker->equals($player)) {

                                $arena = $attacker->getCurrentArena();

                                if($arena->doesHaveKit()) {
                                    $event->setDrops([]);
                                    $kit = $arena->getFirstKit();
                                    $kit->giveTo($p);
                                }

                                $p->setHealth($p->getMaxHealth());

                                $kills = $playerHandler->addKillFor($attacker->getPlayerName());
                                $killsStr = PracticeUtil::str_replace(PracticeUtil::getName('scoreboard.arena-ffa.kills'), ['%num%' => $kills]);
                                $attacker->updateLineOfScoreboard(4, ' ' . $killsStr);
                            }
                        }
                    }
                    $playerHandler->addDeathFor($player->getPlayerName());
                    //$player->updateScoreboard();
                }
            } else {

                if($player->isInDuel()) {

                    $duel = $duelHandler->getDuel($p);

                    $msg = $event->getDeathMessage();

                    $winner = ($duel->isPlayer($p) ? $duel->getOpponent()->getPlayerName() : $duel->getPlayer()->getPlayerName());
                    $loser = $p->getName();

                    $randMsg = PracticeUtil::getRandomDeathMsg($winner, $loser);

                    $msg = (!is_null($randMsg)) ? $randMsg : $msg;

                    $duel->broadcastMsg($msg, true);

                    if($diedFairly === true)
                        $duel->setResults($winner, $loser);
                    else $duel->setResults();

                    $event->setDrops([]);

                    $event->setDeathMessage('');
                }
            }
        }
    }

    public function onRespawn(PlayerRespawnEvent $event): void {

        $p = $event->getPlayer();

        $nameTag = PracticeUtil::getNameTagFormat($p);

        $p->setNameTag($nameTag);

        $spawnPos = PracticeUtil::getSpawnPosition();

        $prevSpawnPos = $event->getRespawnPosition();

        if($prevSpawnPos !== $spawnPos)
            $event->setRespawnPosition($spawnPos);

        $player = PracticeCore::getPlayerHandler()->getPlayer($p);

        if($player !== null) {

            if($player->isInArena()) $player->setCurrentArena(PracticeArena::NO_ARENA);

            if(!$player->canThrowPearl()) $player->setThrowPearl(true);

            if($player->isInCombat()) $player->setInCombat(false);

            $player->setSpawnScoreboard();

            $this->core->getScheduler()->scheduleDelayedTask(new RespawnTask($player), 10);
        }
    }

    public function onEntityDamaged(EntityDamageEvent $event): void {

        $cancel = false;
        $e = $event->getEntity();

        $playerHandler = PracticeCore::getPlayerHandler();

        $duelHandler = PracticeCore::getDuelHandler();

        $cause = $event->getCause();


        if($e instanceof Player) {

            $name = $e->getName();

            if ($cause === EntityDamageEvent::CAUSE_FALL)
                $cancel = true;

            else {

                if ($playerHandler->isPlayerOnline($name)) {
                    
                    $player = $playerHandler->getPlayer($name);

                    $lvl = $player->getPlayer()->getLevel();

                    if (PracticeUtil::areLevelsEqual($lvl, PracticeUtil::getDefaultLevel())) {

                        if($cause === EntityDamageEvent::CAUSE_VOID) {

                            if($duelHandler->isASpectator($name)) {
                                $duel = $duelHandler->getDuelFromSpec($name);
                                $center = $duel->getArena()->getSpawnPosition();
                                PracticeUtil::teleportPlayer($player, $center);
                            } else
                                PracticeUtil::teleportPlayer($player);

                            $event->setCancelled(true);
                            return;
                        }

                        $cancel = boolval(PracticeUtil::isLobbyProtectionEnabled());
                    }

                    if ($cancel === true) $cancel = boolval(!$player->isInDuel()) and boolval(!$player->isInArena());

                } else $cancel = true;

                if(PracticeUtil::isInSpectatorMode($name))
                    $cancel = true;
            }
        }

        if($cancel === true) $event->setCancelled();
    }

    public function onEntityDamagedByEntity(EntityDamageByEntityEvent $event): void {

        $entity = $event->getEntity();
        $damager = $event->getDamager();

        $playerHandler = PracticeCore::getPlayerHandler();

        $duelHandler = PracticeCore::getDuelHandler();

        $kitHandler = PracticeCore::getKitHandler();

        $trackHit = false;

        if($event->getCause() !== EntityDamageEvent::CAUSE_PROJECTILE
            and $entity instanceof Player and $damager instanceof Player) {

            if(AntiCheatUtil::canDamage($entity->getName()) and !$event->isCancelled()) {
                AntiCheatUtil::checkForReach($entity, $damager);
                $trackHit = true;
            }
        }

        $cancel = false;

        if($playerHandler->isPlayerOnline($damager->getName()) and $playerHandler->isPlayerOnline($entity->getName())) {

            $attacker = $playerHandler->getPlayer($damager->getName());
            $attacked = $playerHandler->getPlayer($entity->getName());

            if(!$attacker->canHitPlayer() or !$attacked->canHitPlayer())
                $cancel = true;

            if($cancel === false) {

                $kb = $event->getKnockBack();
                $attackDelay = $event->getAttackCooldown();

                if($attacker->isInDuel() and $attacked->isInDuel()) {

                    $duel = $duelHandler->getDuel($attacker->getPlayerName());

                    $kit = $duel->getQueue();

                    if($kitHandler->hasKitSetting($kit)) {

                        $pvpData = $kitHandler->getKitSetting($kit);

                        $kb = $pvpData->getKB();
                        $attackDelay = $pvpData->getAttackDelay();
                    }
                } elseif ($attacker->isInArena() and $attacked->isInArena()) {

                    $arena = $attacker->getCurrentArena();

                    if($arena->doesHaveKit()) {

                        $kit = $arena->getFirstKit();

                        $name = $kit->getName();

                        if($kitHandler->hasKitSetting($name)) {

                            $pvpData = $kitHandler->getKitSetting($name);

                            $kb = $pvpData->getKB();
                            $attackDelay = $pvpData->getAttackDelay();
                        }
                    }
                }

                $event->setAttackCooldown($attackDelay);
                $event->setKnockBack($kb);

                if(AntiCheatUtil::canDamage($attacked->getPlayerName()) and !$event->isCancelled()) {

                    $attacked->setNoDamageTicks($event->getAttackCooldown());

                    if($trackHit === true) {

                        if($attacker->isSwitching()) {
                            //$attacker->kick('Switching is not allowed.');
                            return;
                        }
                        $attacker->trackHit();
                    }

                    if(!$attacker->isInDuel() and !$attacked->isInDuel()) {

                        $attacker->setInCombat(true);
                        $attacked->setInCombat(true);

                    } else {

                        if($attacked->isInDuel() and $attacked->isInDuel()) {
                            $duel = $duelHandler->getDuel($attacker->getPlayer());

                            if ($duel->isSpleef())
                                $cancel = true;
                            else $duel->addHitFrom($attacked->getPlayer());
                        }
                    }

                    if($cancel === false and $attacked->isInArena()) {
                        $p = $attacked->getPlayer();
                        $nameTag = PracticeUtil::getNameTagFormat($p);
                        $p->setNameTag($nameTag);
                    }
                }
            }
        }

        if($cancel === true) $event->setCancelled();
    }

    public function onEntityDamagedByChildEntity(EntityDamageByChildEntityEvent $event) : void {

        $child = $event->getChild();

        $damaged = $event->getEntity();

        if(!$event->isCancelled() and $child instanceof \pocketmine\entity\projectile\EnderPearl and $damaged instanceof Player) {

            $throwerEntity = $child->getOwningEntity();

            echo 'oedbce - found';

            $playerHandler = PracticeCore::getPlayerHandler();

            if($throwerEntity !== null and $throwerEntity instanceof Player
                and $playerHandler->isPlayerOnline($throwerEntity->getName())) {

                echo 'oedbce - check';

                $thrower = $playerHandler->getPlayer($throwerEntity->getName());

                $thrower->checkSwitching();
            }
        }
    }

    public function onPlayerConsume(PlayerItemConsumeEvent $event): void {

        $item = $event->getItem();
        $p = $event->getPlayer();

        $cancel = false;

        $inv = $p->getInventory();

        if(PracticeUtil::canUseItems($p)) {

            if($item instanceof Food) {

                $isGoldenHead = false;

                if ($item->getId() === Item::GOLDEN_APPLE) $isGoldenHead = ($item->getDamage() === 1 or $item->getName() === PracticeUtil::getName('golden-head'));

                if ($isGoldenHead === true) {

                    /* @var $effects EffectInstance[] */
                    $effects = $item->getAdditionalEffects();

                    $eightSeconds = PracticeUtil::secondsToTicks(8);

                    $twoMin = PracticeUtil::minutesToTicks(2);

                    $keys = array_keys($effects);

                    foreach($keys as $key) {
                        $effect = $effects[$key];
                        $id = $effect->getId();
                        if($id === Effect::REGENERATION)
                            $effect = $effect->setDuration($eightSeconds)->setAmplifier(1);
                        elseif ($id === Effect::ABSORPTION)
                            $effect = $effect->setDuration($twoMin);
                        $effects[$key] = $effect;
                    }

                    foreach($effects as $effect)
                        $p->addEffect($effect);

                    $heldItem = $inv->getHeldItemIndex();

                    $item = $item->setCount($item->count - 1);

                    $inv->setItem($heldItem, $item);

                    $cancel = true;

                } else {
                    if ($item->getId() === Item::MUSHROOM_STEW)
                        $cancel = true;
                }
            } elseif ($item instanceof Potion) {

                $slot = $inv->getHeldItemIndex();
                $effects = $item->getAdditionalEffects();

                $inv->setItem($slot, Item::get(0));

                foreach($effects as $effect) {
                    if($effect instanceof EffectInstance)
                        $p->addEffect($effect);
                }

                $cancel = true;
            }
        } else $cancel = true;

        if($cancel === true) $event->setCancelled();
    }

    public function onPlayerInteract(PlayerInteractEvent $event): void {

        $item = $event->getItem();
        $player = $event->getPlayer();
        $action = $event->getAction();
        $level = $player->getLevel();
        $cancel = false;

        $playerHandler = PracticeCore::getPlayerHandler();

        $itemHandler = PracticeCore::getItemHandler();

        $duelHandler = PracticeCore::getDuelHandler();

        if ($playerHandler->isPlayer($player)) {
            $p = $playerHandler->getPlayer($player);

            $exec = PracticeUtil::checkActions($action, PlayerInteractEvent::RIGHT_CLICK_BLOCK);
            if (($p->getDevice() !== PracticeUtil::WINDOWS_10 or $p->getInput() !== PracticeUtil::CONTROLS_MOUSE) and $exec === true)
                $p->addCps(false);
                //$p->addClick(false);

            if ($itemHandler->isPracticeItem($item)) {

                if (PracticeUtil::checkActions($action, PlayerInteractEvent::RIGHT_CLICK_BLOCK, PlayerInteractEvent::RIGHT_CLICK_AIR)) {

                    $practiceItem = $itemHandler->getPracticeItem($item);

                    if ($practiceItem instanceof PracticeItem and $itemHandler->canUseItem($p, $practiceItem)) {

                        $name = $practiceItem->getLocalizedName();
                        $exec = ($practiceItem->canOnlyUseInLobby() ? PracticeUtil::areLevelsEqual($level, PracticeUtil::getDefaultLevel()) : true);

                        if($exec === true) {

                            if (PracticeUtil::str_contains('hub.', $name)) {
                                if (PracticeUtil::str_contains('unranked-duels', $name)) {
                                    if(PracticeUtil::isItemFormsEnabled()) {
                                        $form = FormUtil::getMatchForm();
                                        $p->sendForm($form, ['ranked' => false]);
                                    } else InventoryUtil::sendMatchInv($player);
                                } elseif (PracticeUtil::str_contains('ranked-duels', $name)) {
                                    if(PracticeUtil::isItemFormsEnabled()) {
                                        $form = FormUtil::getMatchForm(true);
                                        $p->sendForm($form, ['ranked' => true]);
                                    } else InventoryUtil::sendMatchInv($player, true);
                                } elseif (PracticeUtil::str_contains('ffa', $name)) {
                                    if(PracticeUtil::isItemFormsEnabled()) {
                                        $form = FormUtil::getFFAForm();
                                        $p->sendForm($form);
                                    } else InventoryUtil::sendFFAInv($player);
                                } elseif (PracticeUtil::str_contains('duel-inv', $name)) {
                                    $p->spawnResInvItems();
                                } elseif (PracticeUtil::str_contains('settings', $name)) {
                                    $op = PracticeUtil::testPermission($player, PermissionsHandler::PERMISSION_PLACE_BREAK, false);
                                    $form = FormUtil::getSettingsForm($p->getPlayerName(), $op);
                                    $p->sendForm($form);
                                } elseif (PracticeUtil::str_contains('leaderboard', $name))
                                    InventoryUtil::sendLeaderboardInv($player);


                            } elseif ($name === 'exit.inventory') {

                                $itemHandler->spawnHubItems($player, true);

                            } elseif ($name === 'exit.queue') {

                                $duelHandler->removePlayerFromQueue($player, true);
                                $p->setSpawnScoreboard();
                                ScoreboardUtil::updateSpawnScoreboards($p);

                            } elseif ($name === 'exit.spectator') {

                                if($duelHandler->isASpectator($player)) {
                                    $duel = $duelHandler->getDuelFromSpec($player);
                                    $duel->removeSpectator($player->getName(), true);
                                } else PracticeUtil::resetPlayer($player);

                                $msg = PracticeUtil::getMessage('spawn-message');
                                $player->sendMessage($msg);

                            } elseif (PracticeUtil::str_contains('party.', $name)) {

                                $partyManager = PracticeCore::getPartyManager();

                                if(PracticeUtil::str_contains('leader.', $name)) {
                                    //TODO
                                } elseif ($name === 'party.general.leave') {
                                    if(!$partyManager->removePlayerFromParty($player->getName())){
                                        //TODO ADD TO MESSAGES.YML
                                        $msg = TextFormat::RED . 'You are not in a party!';
                                        $player->sendMessage($msg);
                                    }
                                }
                            }
                        }
                    }
                    $cancel = true;
                }
            } else {

                if ($p->isDuelHistoryItem($item)) {

                    if(PracticeUtil::canUseItems($player, true)) {
                        $name = $item->getName();
                        InventoryUtil::sendResultInv($player, $name);
                    }
                    $cancel = true;

                } else {

                    $checkPlaceBlock = $item->getId() < 255 or PracticeUtil::isSign($item) or $item instanceof ItemBlock or $item instanceof Bucket or $item instanceof FlintSteel;

                    if (PracticeUtil::canUseItems($player)) {

                        if($checkPlaceBlock === true) {
                            if($p->isInArena())
                                $cancel = !$p->getCurrentArena()->canBuild();
                            else {
                                if ($p->isInDuel()) {
                                    $duel = $duelHandler->getDuel($p);
                                    if($duel->isDuelRunning() and $duel->canBuild()) {
                                        $cancel = false;
                                    } else $cancel = true;
                                } else {
                                    $cancel = true;
                                    if (!PracticeUtil::isInSpectatorMode($player) and PracticeUtil::testPermission($player, PermissionsHandler::PERMISSION_PLACE_BREAK, false))
                                        $cancel = !$playerHandler->canPlaceNBreak($player->getName());
                                }
                            }
                            $event->setCancelled($cancel);
                            return;
                        }

                        if ($item->getId() === Item::FISHING_ROD) {

                            $use = false;

                            $checkActions = PracticeUtil::checkActions($action, PlayerInteractEvent::RIGHT_CLICK_AIR, PlayerInteractEvent::RIGHT_CLICK_BLOCK);

                            if (PracticeUtil::isTapToRodEnabled()) {
                                if($checkActions === true) {
                                    if($p->getDevice() === PracticeUtil::WINDOWS_10 or $p->getInput() === PracticeUtil::CONTROLS_MOUSE) {
                                        $use = $action !== PlayerInteractEvent::RIGHT_CLICK_BLOCK and $action !== PlayerInteractEvent::LEFT_CLICK_AIR;
                                    } else $use = true;
                                }
                            } else $use = PracticeUtil::checkActions($action, PlayerInteractEvent::RIGHT_CLICK_AIR);

                            if ($use === true) PracticeUtil::useRod($item, $player);
                            else $cancel = true;

                        } elseif ($item->getId() === Item::ENDER_PEARL and $item instanceof EnderPearl) {

                            $use = false;

                            $checkActions = PracticeUtil::checkActions($action, PlayerInteractEvent::RIGHT_CLICK_AIR, PlayerInteractEvent::RIGHT_CLICK_BLOCK);

                            if (PracticeUtil::isTapToPearlEnabled()) {
                                if($checkActions === true) {
                                    if($p->getDevice() === PracticeUtil::WINDOWS_10 or $p->getInput() === PracticeUtil::CONTROLS_MOUSE) {
                                        $use = $action !== PlayerInteractEvent::RIGHT_CLICK_BLOCK and $action !== PlayerInteractEvent::LEFT_CLICK_AIR;
                                    } else $use = true;
                                }
                            } else
                                $use = PracticeUtil::checkActions($action, PlayerInteractEvent::RIGHT_CLICK_AIR);

                            if ($use === true) PracticeUtil::throwPearl($item, $player);

                            $cancel = true;

                        } elseif ($item->getId() === Item::SPLASH_POTION and $item instanceof SplashPotion) {

                            $use = false;

                            $checkActions = PracticeUtil::checkActions($action, PlayerInteractEvent::RIGHT_CLICK_BLOCK, PlayerInteractEvent::RIGHT_CLICK_AIR);

                            if (PracticeUtil::isTapToPotEnabled()) {
                                if($checkActions === true) {
                                    if($p->getDevice() === PracticeUtil::WINDOWS_10 or $p->getInput() === PracticeUtil::CONTROLS_MOUSE) {
                                        $use = $action !== PlayerInteractEvent::RIGHT_CLICK_BLOCK and $action !== PlayerInteractEvent::LEFT_CLICK_AIR;
                                    } else $use = true;
                                }
                            } else {
                                $use = PracticeUtil::checkActions($action, PlayerInteractEvent::RIGHT_CLICK_AIR);
                            }

                            if ($use === true) PracticeUtil::throwPotion($item, $player);

                            $cancel = true;

                        } elseif ($item->getId() === Item::MUSHROOM_STEW and $item instanceof MushroomStew) {

                            $inv = $player->getInventory();

                            $inv->setItemInHand(Item::get(Item::AIR));

                            $newHealth = $player->getHealth() + 7.0;

                            if ($newHealth > $player->getMaxHealth()) $newHealth = $player->getMaxHealth();

                            $player->setHealth($newHealth);

                            $cancel = true;
                        }

                    } else {

                        $cancel = true;

                        if($checkPlaceBlock === true) {
                            if($p->isInArena()) {
                                $cancel = !$p->getCurrentArena()->canBuild();
                            } else {
                                if ($p->isInDuel()) {
                                    $duel = $duelHandler->getDuel($p);
                                    if($duel->isDuelRunning() and $duel->canBuild()) {
                                        $cancel = false;
                                    }
                                } else {
                                    if (!PracticeUtil::isInSpectatorMode($player) and PracticeUtil::testPermission($player, PermissionsHandler::PERMISSION_PLACE_BREAK, false))
                                        $cancel = !$playerHandler->canPlaceNBreak($player->getName());
                                }
                            }
                            $event->setCancelled($cancel);
                            return;
                        }
                    }
                }
            }
        }

        if ($cancel === true) $event->setCancelled();

    }

    public function onBlockPlace(BlockPlaceEvent $event): void {

        $item = $event->getItem();
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $cancel = false;

        $playerHandler = PracticeCore::getPlayerHandler();

        $itemHandler = PracticeCore::getItemHandler();

        $duelHandler = PracticeCore::getDuelHandler();

        if ($playerHandler->isPlayer($player)) {

            $p = $playerHandler->getPlayer($player);

            $name = $player->getName();

            if ($itemHandler->isPracticeItem($item))
                $cancel = true;
            else {
                if($p->isInArena()) {
                    $cancel = !$p->getCurrentArena()->canBuild();
                } else {
                    if ($p->isInDuel()) {
                        $duel = $duelHandler->getDuel($name);
                        if($duel->isDuelRunning() and $duel->canBuild()) {
                            $blockAgainst = $event->getBlockAgainst();
                            $blockReplaced = $event->getBlockReplaced();
                            $place = $duel->canPlaceBlock($blockAgainst);
                            if($place === true)
                                $duel->addBlock($blockReplaced);
                            else $cancel = true;
                        } else $cancel = true;
                    } else {
                        $cancel = true;
                        if (!PracticeUtil::isInSpectatorMode($player) and PracticeUtil::testPermission($player, PermissionsHandler::PERMISSION_PLACE_BREAK, false))
                            $cancel = !$playerHandler->canPlaceNBreak($name);
                    }
                }
            }
        }

        if ($cancel === true) $event->setCancelled();
    }

    public function onBlockBreak(BlockBreakEvent $event): void {

        $item = $event->getItem();
        $player = $event->getPlayer();

        $cancel = false;

        $playerHandler = PracticeCore::getPlayerHandler();

        $itemHandler = PracticeCore::getItemHandler();

        $duelHandler = PracticeCore::getDuelHandler();

        if ($playerHandler->isPlayer($player)) {

            $p = $playerHandler->getPlayer($player);

            if ($itemHandler->isPracticeItem($item))

                $cancel = true;

            else {

                if($p->isInArena()) {

                    $cancel = !$p->getCurrentArena()->canBuild();

                } else {

                    if ($p->isInDuel()) {
                        $duel = $duelHandler->getDuel($player->getName());
                        if($duel->isDuelRunning() and $duel->canBreak())
                            $cancel = !$duel->removeBlock($event->getBlock());
                        else $cancel = true;
                    } else {

                        $cancel = true;

                        if (!PracticeUtil::isInSpectatorMode($player) and PracticeUtil::testPermission($player, PermissionsHandler::PERMISSION_PLACE_BREAK, false))
                            $cancel = !$playerHandler->canPlaceNBreak($player->getName());

                    }
                }
            }
        }

        if ($cancel === true) $event->setCancelled();
    }

    //THE SAME AS BLOCKFROMTOEVENT IN NUKKIT
    public function onBlockReplace(BlockFormEvent $event): void {

        $arenaHandler = PracticeCore::getArenaHandler();

        $duelHandler = PracticeCore::getDuelHandler();

        $arena = $arenaHandler->getArenaClosestTo($event->getBlock());
        $cancel = false;
        if(!is_null($arena) and ($arena->getArenaType() === PracticeArena::DUEL_ARENA)) {
            if($duelHandler->isArenaInUse($arena->getName())) {
                $duel = $duelHandler->getDuel($arena->getName(), true);
                if($duel->isDuelRunning()) {
                    if($event->getNewState() instanceof Liquid)
                        $duel->addBlock($event->getBlock());
                    else $cancel = true;
                }
                else $cancel = true;
            } else {
                $cancel = true;
            }
        } else {
            $cancel = true;
        }

        if($cancel === true) $event->setCancelled();
    }

    //USE FOR LAVA AND LIQUIDS
    public function onBlockSpread(BlockSpreadEvent $event): void {

        $arenaHandler = PracticeCore::getArenaHandler();

        $duelHandler = PracticeCore::getDuelHandler();

        $arena = $arenaHandler->getArenaClosestTo($event->getBlock());
        $cancel = false;
        if(!is_null($arena) and ($arena->getArenaType() === PracticeArena::DUEL_ARENA)) {
            if($duelHandler->isArenaInUse($arena->getName())) {
                $duel = $duelHandler->getDuel($arena->getName(), true);
                if($duel->isDuelRunning()) {
                    if($event->getNewState() instanceof Liquid)
                        $duel->addBlock($event->getBlock());
                    else $cancel = true;
                }
                else $cancel = true;

            } else $cancel = true;

        } else $cancel = true;

        if($cancel === true) $event->setCancelled();
    }

    public function onBucketFill(PlayerBucketFillEvent $event): void {

        $item = $event->getItem();
        $player = $event->getPlayer();

        $playerHandler = PracticeCore::getPlayerHandler();

        $itemHandler = PracticeCore::getItemHandler();

        $duelHandler = PracticeCore::getDuelHandler();

        $cancel = false;

        if ($playerHandler->isPlayer($player)) {

            $p = $playerHandler->getPlayer($player);

            if ($itemHandler->isPracticeItem($item)) {

                $cancel = true;

            } else {

                if($p->isInArena())
                    $cancel = !$p->getCurrentArena()->canBuild();

                else {

                    if ($p->isInDuel()) {
                        $duel = $duelHandler->getDuel($player->getName());
                        if($duel->isDuelRunning() and $duel->canBuild())
                            $cancel = !$duel->removeBlock($event->getBlockClicked());
                        else $cancel = true;

                    } else {

                        $cancel = true;

                        if (!PracticeUtil::isInSpectatorMode($player) and PracticeUtil::testPermission($player, PermissionsHandler::PERMISSION_PLACE_BREAK, false))
                            $cancel = !$playerHandler->canPlaceNBreak($player->getName());

                        if (PracticeUtil::areLevelsEqual($player->getLevel(), PracticeUtil::getDefaultLevel()))
                            $cancel = true;
                    }
                }
            }
        }

        if ($cancel === true) $event->setCancelled();
    }

    public function onBucketEmpty(PlayerBucketEmptyEvent $event): void {

        $item = $event->getBucket();
        $player = $event->getPlayer();
        $cancel = false;

        $playerHandler = PracticeCore::getPlayerHandler();

        $itemHandler = PracticeCore::getItemHandler();

        $duelHandler = PracticeCore::getDuelHandler();

        if ($playerHandler->isPlayer($player)) {

            $p = $playerHandler->getPlayer($player);

            if ($itemHandler->isPracticeItem($item))
                $cancel = true;

            else {
                if($p->isInArena()) {
                    $cancel = !$p->getCurrentArena()->canBuild();
                } else {
                    if ($p->isInDuel()) {
                        $duel = $duelHandler->getDuel($player->getName());
                        if($duel->isDuelRunning() and $duel->canBuild()) {
                            $duel->addBlock($event->getBlockClicked());
                        } else $cancel = true;
                    } else {

                        $cancel = true;

                        if (!PracticeUtil::isInSpectatorMode($player) and PracticeUtil::testPermission($player, PermissionsHandler::PERMISSION_PLACE_BREAK, false))
                            $cancel = !$playerHandler->canPlaceNBreak($player->getName());

                        if(PracticeUtil::areLevelsEqual($player->getLevel(), PracticeUtil::getDefaultLevel()))
                            $cancel = true;
                    }
                }
            }
        }

        if ($cancel === true) $event->setCancelled();
    }

    public function onFireSpread(BlockBurnEvent $event) {
        $event->setCancelled();
    }

    //TODO TEST
    public function onCommandPreprocess(PlayerCommandPreprocessEvent $event): void {

        $p = $event->getPlayer();

        $cancel = false;

        $playerHandler = PracticeCore::getPlayerHandler();

        if ($playerHandler->isPlayer($p)) {

            $player = $playerHandler->getPlayer($p);

            $message = $event->getMessage();

            $firstChar = $message[0];

            $testInAntiSpam = false;

            if($firstChar === '/') {

                $usableCommandsInCombat = ['ping', 'tell', 'say'];

                $tests = ['/ping', '/tell', '/say', '/w'];

                if(PracticeUtil::str_contains('/me', $message)) {
                    $event->setCancelled(true);
                    return;
                }

                $sendMsg = PracticeUtil::str_contains_from_arr($message, $tests);

                if(!$player->canUseCommands(!$sendMsg)) {

                    $use = false;

                    foreach($usableCommandsInCombat as $value) {

                        $value = strval($value);

                        $test = '/' . $value;

                        if(PracticeUtil::str_contains($test, $message)) {
                            $use = true;
                            if($value === 'say') $testInAntiSpam = true;
                            break;
                        }
                    }

                    if($use === false) $cancel = true;
                }
            } else $testInAntiSpam = true;

            if($testInAntiSpam === true) {

                if(PracticeUtil::canPlayerChat($p)) {
                    if($player->isInAntiSpam()) {
                        $player->sendMessage(PracticeUtil::getMessage('antispam-msg'));
                        $cancel = true;
                    }
                } else $cancel = true;
            }
        }

        if ($cancel === true) $event->setCancelled();
    }

    public function onChat(PlayerChatEvent $event): void {

        $p = $event->getPlayer();
        $cancel = false;

        $playerHandler = PracticeCore::getPlayerHandler();

        if (PracticeUtil::isRanksEnabled()) {
            $message = $event->getMessage();
            $event->setFormat(PracticeUtil::getChatFormat($p, $message));
        }

        if (!PracticeUtil::canPlayerChat($p)) $cancel = true;
        else {
            $player = $playerHandler->getPlayer($p);
            if ($player !== null) {
                $player = $playerHandler->getPlayer($p);
                if (!$player->isInAntiSpam())
                    $player->setInAntiSpam(true);
                else {
                    $player->sendMessage(PracticeUtil::getMessage('antispam-msg'));
                    $cancel = true;
                }
            }
        }

        if ($cancel === true) $event->setCancelled();
    }


    public function onPacketSend(DataPacketSendEvent $event): void {

        $pkt = $event->getPacket();

        if ($pkt instanceof TextPacket) {

            if (!PracticeUtil::isChatFilterEnabled()) {
                if ($pkt->type !== TextPacket::TYPE_TRANSLATION) {
                    $pkt->message = PracticeUtil::getUnFilteredChat($pkt->message);
                }

                $count = 0;
                foreach ($pkt->parameters as $param) {
                    $pkt->parameters[$count] = PracticeUtil::getUnFilteredChat(strval($param));
                    $count++;
                }
            }
        } elseif ($pkt instanceof ContainerClosePacket) {
            if($pkt->windowId === -1) $event->setCancelled();
        }
    }

    public function onPacketReceive(DataPacketReceiveEvent $event): void {

        $pkt = $event->getPacket();
        $player = $event->getPlayer();

        $playerHandler = PracticeCore::getPlayerHandler();

        if ($pkt instanceof LoginPacket) {

            $ip = $player->getAddress();

            $ipSafe = PracticeCore::getIPHandler()->isIpSafe($ip);

            if($ipSafe === false) {
                $player->kick('Turn off your VPN/Proxy to play.', false);
                return;
            }

            $clientData = $pkt->clientData;

            $device = (isset($clientData['DeviceOS'])) ? intval($clientData['DeviceOS']) : -1;

            $input = (isset($clientData['CurrentInputMode'])) ? intval($clientData['CurrentInputMode']) : -1;

            $cid = (isset($clientData['ClientRandomId'])) ? intval($clientData['ClientRandomId']) : -1;

            $deviceID = (isset($clientData['DeviceId'])) ? strval($clientData['DeviceId']) : '';

            $deviceModel = (isset($clientData['DeviceModel'])) ? strval($clientData['DeviceModel']) : '';

            if($player !== null) $playerHandler->putPendingPInfo($pkt->username, $device, $input, $cid, $deviceID, $deviceModel);
        }

        if ($pkt instanceof PlayerActionPacket) {
            if ($pkt->action === PlayerActionPacket::ACTION_START_BREAK and $playerHandler->isPlayer($player)) {
                $p = $playerHandler->getPlayer($player);
                if ($p->getDevice() === PracticeUtil::WINDOWS_10 or $p->getDevice() === PracticeUtil::CONTROLS_MOUSE)
                    $p->addCps(true);
            }
        } elseif ($pkt instanceof LevelSoundEventPacket) {

            $sound = $pkt->sound;
            $sounds = [41, 42, 43];

            $cancel = PracticeUtil::arr_contains_value($sound, $sounds);

            if ($cancel === true) {

                if ($playerHandler->isPlayer($player)) {

                    $p = $playerHandler->getPlayer($player);

                    $p->addCps(true);

                    $pl = $p->getPlayer();

                    $inv = $pl->getInventory();

                    $item = $inv->getItemInHand();

                    if (PracticeUtil::canUseItems($pl)) {

                        if ($item->getId() === Item::FISHING_ROD) {
                            if (PracticeUtil::isTapToRodEnabled()) PracticeUtil::useRod($item, $pl, $p->getDevice() !== PracticeUtil::WINDOWS_10 and $p->getInput() !== PracticeUtil::CONTROLS_MOUSE);
                        } elseif ($item->getId() === Item::ENDER_PEARL and $item instanceof EnderPearl) {
                            if (PracticeUtil::isTapToPearlEnabled() and $p->getDevice() !== PracticeUtil::WINDOWS_10 and $p->getInput() !== PracticeUtil::CONTROLS_MOUSE) PracticeUtil::throwPearl($item, $pl, true);
                        } elseif ($item->getId() === Item::SPLASH_POTION and $item instanceof SplashPotion) {
                            if (PracticeUtil::isTapToPotEnabled() and $p->getDevice() !== PracticeUtil::WINDOWS_10 and $p->getInput() !== PracticeUtil::CONTROLS_MOUSE) PracticeUtil::throwPotion($item, $pl, true);
                        } elseif ($item->getId() === Item::MUSHROOM_STEW and $item instanceof MushroomStew) {
                            if ($p->getDevice() !== PracticeUtil::WINDOWS_10 and $p->getInput() !== PracticeUtil::CONTROLS_MOUSE) {
                                $inv->setItemInHand(Item::get(Item::AIR));
                                $newHealth = $player->getHealth() + 7.0;
                                if ($newHealth > $player->getMaxHealth()) $newHealth = $player->getMaxHealth();
                                $player->setHealth($newHealth);
                            }
                        }
                    }
                }

                $event->setCancelled();
            }
        }
    }

    public function onInventoryClosed(InventoryCloseEvent $event) : void {

        $p = $event->getPlayer();

        $playerHandler = PracticeCore::getPlayerHandler();

        if($playerHandler->isPlayerOnline($p)) {
            $inv = $event->getInventory();
            if($inv instanceof PracBaseInv) {
                $menu = $inv->getMenu();
                $menu->onInventoryClosed($p);
            }
        }
    }

    public function onItemMoved(InventoryTransactionEvent $event): void {

        $transaction = $event->getTransaction();
        $p = $transaction->getSource();
        $lvl = $p->getLevel();
        $cancel = false;

        $playerHandler = PracticeCore::getPlayerHandler();

        if($playerHandler->isPlayerOnline($p)) {

            $player = $playerHandler->getPlayer($p);

            $testInv = false;

            if (PracticeUtil::areLevelsEqual($lvl, PracticeUtil::getDefaultLevel())) {

                if(PracticeUtil::isLobbyProtectionEnabled()) {

                    $cancel = !$player->isInDuel() and !$player->isInArena();

                    $testInv = true;

                    if($cancel === true and PracticeUtil::testPermission($p, PermissionsHandler::PERMISSION_PLACE_BREAK, false))
                        $cancel = !$playerHandler->canPlaceNBreak($p->getName());
                }

            } else $testInv = true;

            $testInv = ($cancel === false) ? true : $testInv;

            if($testInv === true) {

                $actions = $transaction->getActions();

                foreach($actions as $action){

                    if($action instanceof SlotChangeAction){

                        $inventory = $action->getInventory();

                        if($inventory instanceof PracBaseInv){

                            $menu = $inventory->getMenu();

                            $menu->onItemMoved($player, $action);

                            if(!$menu->canEdit()) $cancel = true;
                        }
                    }
                }
            }
        } else $cancel = true;

        if ($cancel === true) $event->setCancelled();
    }

    public function onItemDropped(PlayerDropItemEvent $event): void {

        $p = $event->getPlayer();
        $cancel = false;

        $playerHandler = PracticeCore::getPlayerHandler();

        if ($playerHandler->isPlayer($p)) {

            $player = $playerHandler->getPlayer($p);
            $level = $p->getLevel();

            if (PracticeUtil::isLobbyProtectionEnabled())
                $cancel = PracticeUtil::areLevelsEqual($level, PracticeUtil::getDefaultLevel()) or $player->isInDuel();

            if($cancel === false)
                $cancel = PracticeUtil::isInSpectatorMode($p);
        }

        if ($cancel === true) $event->setCancelled();
    }

    public function onPluginDisabled(PluginDisableEvent $event) : void {

        $plugin = $event->getPlugin();

        $playerHandler = PracticeCore::getPlayerHandler();

        $duelHandler = PracticeCore::getDuelHandler();

        $server = $plugin->getServer();

        if($plugin->getName() === PracticeUtil::PLUGIN_NAME) {

            $onlinePlayers = $server->getOnlinePlayers();

            foreach($onlinePlayers as $player) {

                if($playerHandler->isPlayerOnline($player)) {

                    $p = $playerHandler->getPlayer($player);

                    if(!$p->isInDuel()) {

                        PracticeUtil::resetPlayer($player);

                    } else {

                        $duel = $duelHandler->getDuel($player);

                        if(!$duel->didDuelEnd())
                            $duel->endDuelPrematurely(true);
                    }
                }
            }
        }

        $levels = $server->getLevels();

        foreach($levels as $lvl)
            PracticeUtil::clearEntitiesIn($lvl, false, true);

        if($server->isRunning())
            PracticeUtil::kickAll('Restarting Server', false);
    }
}