<?php

declare(strict_types=1);

namespace jkorn\practice\player;


use jkorn\practice\display\DisplayStatisticNames;
use jkorn\practice\entities\FishingHook;
use jkorn\practice\games\misc\gametypes\IGame;
use jkorn\practice\games\misc\managers\awaiting\IAwaitingManager;
use jkorn\practice\games\misc\gametypes\ISpectatorGame;
use jkorn\practice\kits\IKit;
use jkorn\practice\messages\IPracticeMessages;
use jkorn\practice\messages\managers\PracticeMessageManager;
use jkorn\practice\player\misc\FormURLImageHandler;
use jkorn\practice\player\misc\PracticePlayerSessionAdapter;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\form\Form;
use pocketmine\item\Item;
use pocketmine\item\ProjectileItem;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\SourceInterface;
use pocketmine\Player;
use pocketmine\utils\UUID;
use jkorn\practice\data\PracticeDataManager;
use jkorn\practice\items\ItemManager;
use jkorn\practice\player\info\ActionsInfo;
use jkorn\practice\player\info\ClicksInfo;
use jkorn\practice\player\info\ClientInfo;
use jkorn\practice\player\info\CombatInfo;
use jkorn\practice\player\info\DisguiseInfo;
use jkorn\practice\player\info\settings\SettingsInfo;
use jkorn\practice\player\info\stats\StatsInfo;
use jkorn\practice\PracticeCore;
use jkorn\practice\PracticeUtil;
use jkorn\practice\scoreboard\ScoreboardData;

/**
 * Class PracticePlayer
 * @package jkorn\practice\player
 *
 * The default Practice player class.
 */
class PracticePlayer extends Player implements IPracticeMessages
{

    /** @var ScoreboardData|null */
    protected $scoreboardData = null;

    /** @var UUID - Allows for disguises. */
    protected $serverUUID;

    // The player's client information.
    /** @var string */
    protected $version = "";

    /** @var ClientInfo|null */
    protected $clientInfo = null;
    /** @var DisguiseInfo|null */
    protected $disguiseInfo = null;
    /** @var SettingsInfo */
    protected $settingsInfo;
    /** @var ActionsInfo */
    protected $actionInfo;
    /** @var ClicksInfo */
    protected $clicksInfo;
    /** @var StatsInfo */
    protected $statsInfo;
    /** @var CombatInfo|null */
    protected $combatInfo = null;
    /** @var FormURLImageHandler */
    private $urlImageHandler;

    /** @var IKit|null */
    private $equippedKit = null;

    /** @var bool */
    private $doSave = true, $didSave = false;
    /** @var bool - Determines if player is viewing form. */
    private $lookingAtForm = false;

    /** @var bool */
    private $fakeSpectating = false;

    /** @var FishingHook|null */
    private $fishing = null;

    /**
     * PracticePlayer constructor.
     * @param SourceInterface $interface
     * @param string $ip
     * @param int $port
     *
     * Overriden practice player constructor.
     */
    public function __construct(SourceInterface $interface, string $ip, int $port)
    {
        parent::__construct($interface, $ip, $port);

        // Change the session adapter so we can track when we get network stack latency.
        $this->sessionAdapter = new PracticePlayerSessionAdapter($this->server, $this);
        $this->serverUUID = UUID::fromRandom();

        $this->initializeSettings();
    }

    /**
     * Initializes the settings information.
     */
    private function initializeSettings(): void
    {
        $this->settingsInfo = new SettingsInfo();
        $this->actionInfo = new ActionsInfo();
        $this->clicksInfo = new ClicksInfo();
        $this->statsInfo = new StatsInfo();
        $this->urlImageHandler = new FormURLImageHandler($this);
    }

    /**
     * @param NetworkStackLatencyPacket $packet
     * @return bool
     *
     * Called when the player handles network stack latency.
     */
    public function handleNetworkStackLatency(NetworkStackLatencyPacket $packet): bool
    {
        $timeStamp = $packet->timestamp;
        $this->urlImageHandler->onReceive($timeStamp);
        return true;
    }

    /**
     * @param LoginPacket $packet
     * @return bool
     *
     * Called when the player logs in.
     */
    public function handleLogin(LoginPacket $packet): bool
    {
        $this->version = (string)$packet->clientData["GameVersion"];
        if(!parent::handleLogin($packet))
        {
            return false;
        }

        $this->clientInfo = new ClientInfo($packet->clientData);
        return true;
    }

    /**
     * @param PlayerActionPacket $packet
     * @return bool
     *
     * Called when the player does an action.
     */
    public function handlePlayerAction(PlayerActionPacket $packet): bool
    {
        if(parent::handlePlayerAction($packet))
        {
            if(!$this->isOnline())
            {
                return true;
            }

            $this->actionInfo->setAction(
                $packet->action,
                new Position($packet->x, $packet->y, $packet->z, $this->getLevel())
            );

            if($this->actionInfo->didClickBlock() && $this->clientInfo !== null && !$this->clientInfo->isPE())
            {
                $this->onClick(true);
            }

            return true;
        }

        return false;
    }

    /**
     * @param LevelSoundEventPacket $packet
     * @return bool
     *
     * Handles when player receives a sound.
     */
    public function handleLevelSoundEvent(LevelSoundEventPacket $packet): bool
    {
        if(isset(PracticeUtil::SWISH_SOUNDS[$packet->sound]))
        {
            $this->onClick(false);
        }

        // Broadcasts the packet to the viewers based on their information.
        PracticeUtil::broadcastPacketToViewers($this, $packet,
            function(Player $player, DataPacket $packet) {
                if ($player instanceof PracticePlayer && $packet instanceof LevelSoundEventPacket) {
                    // Isolates swish sounds.
                    if (!isset(PracticeUtil::SWISH_SOUNDS[$packet->sound])) {
                        return true;
                    }

                    $settings = $player->getSettingsInfo();
                    $property = $settings->getProperty(SettingsInfo::SWISH_SOUNDS_ENABLED);
                    if ($property !== null) {
                        return $property->getValue();
                    }
                }
                return true;
            });

        return true;
    }

    /**
     * @param $data
     *
     * Loads the data accordingly.
     */
    public function loadData($data): void
    {
        // Extracts the information from the data & initializes the settings.
        SettingsInfo::extract($data, $this->settingsInfo);
        StatsInfo::extract($data, $this->statsInfo);

        // Checks whether the data is an array, gets the information.
        if(is_array($data))
        {
            // Enables player disguise.
            if(isset($data["disguised"]) && (bool)$data["disguised"])
            {
                $this->enableDisguise();
            }
        }

        // Automatically sets the scoreboard type.
        $this->scoreboardData = new ScoreboardData($this, ScoreboardData::SCOREBOARD_SPAWN_DEFAULT);
    }

    /**
     * Exports the data to the server.
     *
     * @return array
     */
    public function exportData(): array
    {
        return [

            // The player settings.
            $this->settingsInfo->getHeader() => $this->settingsInfo->export(),
            $this->statsInfo->getHeader() => $this->statsInfo->export(),
            "disguised" => $this->isDisguised()
        ];
    }

    /**
     * @param bool $save
     *
     * Determines whether or not the server should save the player's data.
     */
    public function setSaveData(bool $save): void
    {
        $this->doSave = $save;
    }

    /**
     * @return bool
     *
     * Determines whether or not the server should save this player's data.
     */
    public function doSaveData(): bool
    {
        return $this->doSave;
    }

    /**
     * @param bool $saved
     *
     * Determines whether or not the player was saved.
     */
    public function setSaved(bool $saved): void
    {
        $this->didSave = $saved;
    }

    /**
     * @return bool
     *
     * Determines whether or not the player has already saved.
     */
    public function isSaved(): bool
    {
        return $this->didSave;
    }

    /**
     * @param PlayerJoinEvent $event
     *
     * Called when the player first joins.
     */
    public function onJoin(PlayerJoinEvent &$event): void
    {
        // Starts loading the data for the player.
        $dataProvider = PracticeDataManager::getDataProvider();
        $dataProvider->loadPlayer($this);

        // Sends the given items to the player.
        $this->putInLobby(true);

        // Sets the join message of the event.
        $practiceMessageManager = PracticeCore::getBaseMessageManager()->getMessageManager(PracticeMessageManager::NAME);
        if($practiceMessageManager !== null)
        {
            $joinMessage = $practiceMessageManager->getMessage(self::PLAYER_JOIN_MESSAGE);
            if($joinMessage !== null)
            {
                $event->setJoinMessage($joinMessage->getText($this));
            }
        }
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * Called when the current player leaves the game.
     */
    public function onLeave(PlayerQuitEvent &$event): void
    {
        // Removes the player from the game queue.
        $awaitingGame = $this->getAwaitingManager();
        if($awaitingGame !== null)
        {
            $awaitingGame->removeAwaiting($this, false);
        }

        // Gets the current game the player is playing in and
        // removes the player from it.
        $game = $this->getCurrentGame();
        if($game !== null)
        {
            $game->removeFromGame($this, IGame::REASON_LEFT_SERVER);
        }

        // Sets the leave message of the event.
        $practiceMessageManager = PracticeCore::getBaseMessageManager()->getMessageManager(PracticeMessageManager::NAME);
        if($practiceMessageManager !== null)
        {
            $leaveMessage = $practiceMessageManager->getMessage(self::PLAYER_LEAVE_MESSAGE);
            if($leaveMessage !== null)
            {
                $event->setQuitMessage($leaveMessage->getText($this));
            }
        }

        $dataProvider = PracticeDataManager::getDataProvider();
        // Saves the player as non async if server isn't running, etc...
        $dataProvider->savePlayer($this, true);
    }

    /**
     * @param int $currentTick
     * @return bool
     *
     * Called to update the player's information.
     */
    public function onUpdate(int $currentTick): bool
    {
        $update = parent::onUpdate($currentTick);
        if($update)
        {
            $this->updateInfo($currentTick);
        }

        return $update;
    }

    /**
     * @param int $currentTick
     *
     * Updates the information of the player.
     */
    protected function updateInfo(int $currentTick): void
    {
        // Updates the scoreboard data accordingly.
        if($currentTick % 2 === 0 && $this->scoreboardData !== null)
        {
            $this->scoreboardData->update();
        }

        if($currentTick % 20 === 0)
        {
            if($this->combatInfo !== null)
            {
                $this->combatInfo->update();
            }
        }

        $this->urlImageHandler->update();
    }

    /**
     * Updates the scoreboard display, only called after their settings is updated.
     * This shouldn't be called anywhere else.
     */
    public function settingsUpdateScoreboard(): void
    {
        if($this->scoreboardData !== null)
        {
            $this->scoreboardData->reloadScoreboard();
        }
    }

    /**
     * @return ScoreboardData|null
     *
     * Gets the player's scoreboard data.
     */
    public function getScoreboardData(): ?ScoreboardData
    {
        return $this->scoreboardData;
    }

    /**
     * @return ClientInfo|null
     *
     * Gets the client information of the player.
     */
    public function getClientInfo(): ?ClientInfo
    {
        return $this->clientInfo;
    }

    /**
     * @return SettingsInfo
     *
     * Gets the player settings information.
     */
    public function getSettingsInfo(): SettingsInfo
    {
        return $this->settingsInfo;
    }

    /**
     * @return ClicksInfo
     *
     * Gets the clicks info of the player.
     */
    public function getClicksInfo(): ClicksInfo
    {
        return $this->clicksInfo;
    }

    /**
     * @return StatsInfo
     *
     * Gets the statistics information of the player.
     */
    public function getStatsInfo(): StatsInfo
    {
        return $this->statsInfo;
    }

    /**
     * @return CombatInfo|null
     *
     * Gets the player's combat information.
     */
    public function getCombatInfo(): ?CombatInfo
    {
        if(!$this->isOnline())
        {
            return null;
        }

        if($this->combatInfo === null)
        {
            return $this->combatInfo = new CombatInfo($this);
        }

        return $this->combatInfo;
    }

    /**
     * @return FormURLImageHandler
     */
    public function getFormURLImageHandler(): FormURLImageHandler
    {
        return $this->urlImageHandler;
    }

    /**
     * @param bool $clickedBlock
     *
     * Called when the player clicks.
     */
    public function onClick(bool $clickedBlock): void
    {
        $this->clicksInfo->addClick($clickedBlock);

        // Called so that the player uses the item on left click (if they are pe).
        $clientInfo = $this->getClientInfo();
        if($clientInfo !== null && $clientInfo->isPE() && !$clickedBlock) {
            $item = $this->getInventory()->getItemInHand();
            $tapItem = PracticeCore::getItemManager()->getTapItem($item);
            if($tapItem !== null) {
                $tapItem->onItemUse($this, $item, PlayerInteractEvent::LEFT_CLICK_AIR);
            }
        }
    }

    /**
     * @param Vector3 $v3
     * @return bool
     *
     * Determines if the player is equivalent to another player.
     */
    public function equals(Vector3 $v3): bool
    {
        if($v3 instanceof PracticePlayer)
        {
            return $v3->serverUUID->equals($this->serverUUID);
        }

        return false;
    }

    /**
     * @return UUID
     *
     * Gets the server id of the player (only used for disguise)
     */
    public function getServerID(): UUID
    {
        return $this->serverUUID;
    }

    /**
     * @return bool
     *
     * Determines whether or not the player is disguised or not.
     */
    public function isDisguised(): bool
    {
        return $this->disguiseInfo !== null;
    }

    /**
     * Enables the disguise.
     */
    public function enableDisguise(): void
    {
        if($this->isDisguised())
        {
            return;
        }

        $this->disguiseInfo = new DisguiseInfo($this->getSkin(), $this->getDisplayName());

        $this->setDisplayName($this->disguiseInfo->getName());

        $this->setSkin($this->disguiseInfo->getSkin());
        $this->sendSkin();
    }

    /**
     * Disables the disguise.
     */
    public function disableDisguise(): void
    {
        if(!$this->isDisguised())
        {
            return;
        }

        $this->setDisplayName($this->disguiseInfo->getOldName());

        $this->setSkin($this->disguiseInfo->getOldSkin());
        $this->sendSkin();

        $this->disguiseInfo = null;
    }


    /**
     * @param IKit $kit
     *
     * Sets the player as equipped with a kit.
     */
    public function setEquipped(IKit $kit): void
    {
        $this->equippedKit = $kit;
    }

    /**
     * @return bool
     *
     * Determines whether or not the player is
     * equipped with a kit.
     */
    public function isEquipped(): bool
    {
        return $this->equippedKit !== null;
    }

    /**
     * @return IKit|null
     *
     * Gets the equipped kit for the player.
     */
    public function getEquippedKit(): ?IKit
    {
        return $this->equippedKit;
    }

    /**
     * @return IGame|null
     *
     * Gets the current game of the player.
     */
    public function getCurrentGame(): ?IGame
    {
        return PracticeCore::getBaseGameManager()->getGame($this);
    }

    /**
     * @return bool
     *
     * Determines whether or not the player is in a game.
     */
    public function isInGame(): bool
    {
        $game = $this->getCurrentGame();
        return $game !== null;
    }

    /**
     * Sets the player as a fake spectator:
     * - Survival Mode
     * - Invisible
     * - Flying Permissions
     *
     * @param bool $spectating - Determines whether the player is spectating or not.
     */
    public function setFakeSpectating(bool $spectating): void
    {
        if($spectating === $this->fakeSpectating)
        {
            return;
        }

        if($spectating)
        {
            $this->setGamemode(0);
        }

        $this->setInvisible($spectating);
        $this->setAllowFlight($spectating);

        if(!$spectating)
        {
            $this->setFlying(false);
        }

        $this->fakeSpectating = $spectating;
    }

    /**
     * @return bool
     *
     * Determines whether the player is fake spectating.
     */
    public function isFakeSpectating(): bool
    {
        return $this->fakeSpectating;
    }

    /**
     * @return ISpectatorGame|null
     *
     * Gets the spectating game of the player.
     */
    public function getSpectatingGame(): ?ISpectatorGame
    {
        return PracticeCore::getBaseGameManager()->getSpectatingGame($this);
    }

    /**
     * @return bool
     *
     * Determines if the player is spectating a game.
     */
    public function isSpectatingGame(): bool
    {
        $game = $this->getSpectatingGame();
        return $game !== null;
    }

    /**
     * @return IAwaitingManager|null - Returns the game manager if player
     *                   is awaiting a game, null otherwise.
     *
     *
     * Determines if this player is awaiting a game.
     */
    public function getAwaitingManager(): ?IAwaitingManager
    {
        return PracticeCore::getBaseGameManager()->getAwaitingManager($this);
    }

    /**
     * @return bool
     *
     * Determines if the player is awaiting for a game.
     */
    public function isAwaitingForGame(): bool
    {
        $awaiting = $this->getAwaitingManager();
        return $awaiting !== null;
    }

    /**
     * @return bool
     *
     * Determines if the player is in the lobby.
     */
    public function isInLobby(): bool
    {
        return PracticeUtil::areLevelsEqual(
            $this->getLevel(),
            $this->server->getDefaultLevel()
        );
    }

    /**
     * Clears the entire inventory of the player.
     */
    public function clearInventory(): void
    {
        $this->getInventory()->clearAll();
        $this->getArmorInventory()->clearAll();
    }

    /**
     * @return float - The max saturation.
     * Returns the maximum saturation of the player.
     */
    public function getMaxSaturation(): float
    {
        return $this->attributeMap->getAttribute(Attribute::SATURATION)->getMaxValue();
    }

    /**
     * @param EntityDamageEvent $event
     *
     * Overrides the attack function by checking if the player can actually be damaged.
     */
    public function attack(EntityDamageEvent $event): void
    {
        if(!$this->canBeDamaged($event))
        {
            $event->setCancelled(true);
            return;
        }

        parent::attack($event);

        $this->onPostAttack($event);
    }

    /**
     * Called when the player has died.
     */
    protected function onDeath(): void
    {
        parent::onDeath();

        // Adds a death to the total player deaths.
        $statisticsInformation = $this->getStatsInfo();
        $totalDeathsStatistic = $statisticsInformation->getStatistic(DisplayStatisticNames::STATISTIC_TOTAL_PLAYER_DEATHS);
        if($totalDeathsStatistic !== null)
        {
            $totalDeathsStatistic->setValue($totalDeathsStatistic->getValue() + 1);
        }

        // Adds a kill to the player that killed the current player.
        $lastDamageCause = $this->getLastDamageCause();
        if($lastDamageCause instanceof EntityDamageByEntityEvent)
        {
            $damager = $lastDamageCause->getDamager();
            if($damager instanceof PracticePlayer)
            {
                $cause = $lastDamageCause->getCause();
                if($cause === EntityDamageEvent::CAUSE_VOID || $cause === EntityDamageEvent::CAUSE_SUICIDE)
                {
                    return;
                }
                $damagerStatistics = $damager->getStatsInfo();
                $damagerKills = $damagerStatistics->getStatistic(DisplayStatisticNames::STATISTIC_TOTAL_PLAYER_KILLS);
                if($damagerKills !== null)
                {
                    $damagerKills->setValue($damagerKills->getValue() + 1);
                }
            }
        }
    }

    /**
     * @param EntityDamageEvent &$event - The input event.
     *
     * @return bool
     *
     * Determines if the player can be damaged.
     */
    public function canBeDamaged(EntityDamageEvent &$event): bool
    {
        if($this->isCreative() || $this->isSpectator() || $this->isFakeSpectating())
        {
            return false;
        }

        // Checks whether the damager is a fake spectator and cancels the event.
        if($event instanceof EntityDamageByEntityEvent)
        {
            $damager = $event->getDamager();
            if($damager instanceof PracticePlayer && $damager->isFakeSpectating())
            {
                return false;
            }
        }

        return true;
    }

    /**
     * @param EntityDamageEvent $event
     *
     * Called after the attack occurred.
     */
    protected function onPostAttack(EntityDamageEvent &$event): void
    {
        if($event->isCancelled())
        {
            return;
        }

        if(
            $this->isEquipped()
            && $event instanceof EntityDamageByEntityEvent
            && ($damager = $event->getDamager()) !== null
            && $damager instanceof PracticePlayer)
        {
            $speed = $event->getAttackCooldown();
            if($damager->equippedKit !== null && $damager->equippedKit->equals($this->equippedKit))
            {
                $speed = $this->equippedKit->getCombatData()->getSpeed();
            }

            // Updates the attack speed.
            $this->attackTime = $speed;
        }
    }

    /**
     * @param Entity $attacker - The attacker.
     * @param float $damage - The damage accordingly.
     * @param float $x - The x knockback
     * @param float $z
     * @param float $base
     *
     * Gives knockback to the player.
     */
    public function knockBack(Entity $attacker, float $damage, float $x, float $z, float $base = 0.4): void
    {
        $xzKb = $base; $yKb = $base;
        if($attacker instanceof PracticePlayer && $attacker->isEquipped() && $attacker->getEquippedKit()->equals($this->equippedKit))
        {
            $xzKb = ($combatData = $this->equippedKit->getCombatData())->getXZ();
            $yKb = $combatData->getY();
        }

        $f = sqrt($x * $x + $z * $z);
        if($f <= 0){
            return;
        }
        if(mt_rand() / mt_getrandmax() > $this->getAttributeMap()->getAttribute(Attribute::KNOCKBACK_RESISTANCE)->getValue()){
            $f = 1 / $f;

            $motion = clone $this->motion;

            $motion->x /= 2;
            $motion->y /= 2;
            $motion->z /= 2;
            $motion->x += $x * $f * $xzKb;
            $motion->y += $yKb;
            $motion->z += $z * $f * $xzKb;

            if($motion->y > $yKb){
                $motion->y = $yKb;
            }

            $this->setMotion($motion);
        }
    }

    /**
     * @param bool $teleport
     * @param bool $resetScoreboard - Determines whether to reset the scoreboard.
     *
     * Puts the player in the lobby.
     */
    public function putInLobby(bool $teleport, bool $resetScoreboard = true): void
    {
        // TODO: Add more things.
        if($teleport)
        {
            $this->teleport(PracticeUtil::getLobbySpawn());
        }

        // Sets the scoreboard.
        $scoreboard = $this->getScoreboardData();
        if(
            $resetScoreboard
            && $scoreboard !== null
        )
        {
            $scoreboard->setScoreboard(ScoreboardData::SCOREBOARD_SPAWN_DEFAULT);
        }

        // Removes all of the effects.
        $this->removeAllEffects();
        PracticeCore::getItemManager()->sendItemsFromType(ItemManager::TYPE_LOBBY, $this);
    }

    /**
     * @param Form $form
     *
     * Sends the form to the player, overriden so player is bombarded with forms.
     */
    public function sendForm(Form $form): void
    {
        if($this->lookingAtForm)
        {
            return;
        }

        $this->lookingAtForm = true;
        parent::sendForm($form);
    }


    /**
     * @param int $formId
     * @param mixed $responseData
     * @return bool
     *
     * Determines which form was submitted, overriden so player can start viewing forms again.
     */
    public function onFormSubmit(int $formId, $responseData): bool
    {
        $this->lookingAtForm = false;
        return parent::onFormSubmit($formId, $responseData);
    }

    /**
     * @param Position $position
     *
     * Teleports the player on chunk generated, usually called when a player
     * first is teleported to a newly generated level.
     */
    public function teleportOnChunkGenerated(Position $position): void
    {
        $player = $this->getPlayer();
        PracticeUtil::onChunkGenerated($position->getLevelNonNull(), $position->x >> 4, $position->z >> 4, function() use($player, $position)
        {
            $player->teleport($position);
        });

        if(!PracticeUtil::areLevelsEqual($this->getLevelNonNull(), $position->getLevelNonNull()))
        {
            $this->teleport($position);
        }
    }

    /**
     * @param int $animation - The animation being sent to the player.
     *
     * Sends an animation to the player.
     */
    public function sendAnimation(int $animation): void
    {
        $packet = new AnimatePacket();
        $packet->entityRuntimeId = $this->getId();
        $packet->action = $animation;
        $this->server->broadcastPacket($this->getViewers(), $packet);
    }

    /**
     * @param ProjectileItem $item - The projectile item that the player uses.
     * @param bool $animate - Called to animate the player.
     * @return bool
     *
     * Called to force a player to throw a projectile.
     */
    public function throwProjectile(ProjectileItem $item, bool $animate): bool
    {
        if(!$this->spawned || !$this->isAlive() || $this->isSpectator() || $this->isFakeSpectating())
        {
            return true;
        }

        $item->onClickAir($this, $this->getDirectionVector());
        if($animate) {
            $this->sendAnimation(AnimatePacket::ACTION_SWING_ARM);
        }

        if(!$this->isCreative()) {
            if(!$item->isNull()) {
                $item->pop();
            } else {
                $item = Item::get(Item::AIR);
            }
            $this->getInventory()->setItemInHand($item);
        }

        return true;
    }

    /**
     * @return bool
     *
     * Determines if the player is fishing or not.
     */
    public function isFishing(): bool
    {
        return $this->fishing !== null;
    }

    /**
     * Forces the player to stop fishing.
     *
     * @param bool $click - Determines if the player has clicked.
     * @param bool $killEntity - Determines whether to kill the fishing rod or not.
     */
    public function stopFishing(bool $click = true, bool $killEntity = true): void
    {
        if($this->fishing !== null)
        {
            if($click) {
                $this->fishing->reelLine();
            } elseif (!$this->fishing->isClosed() && $killEntity) {
                $this->fishing->kill();
                $this->fishing->close();
            }
        }
        $this->fishing = null;
    }

    /**
     * Called when the player is fishing.
     */
    private function startFishing(): void
    {
        if($this->isFishing()) {
            return;
        }

        $tag = Entity::createBaseNBT($this->add(0.0, $this->getEyeHeight(), 0.0), $this->getDirectionVector(), (float)$this->yaw, (float)$this->pitch);
        $rod = Entity::createEntity("FishingHook", $this->getLevelNonNull(), $tag, $this);

        if($rod !== null && $rod instanceof FishingHook)
        {
            $x = -sin(deg2rad($this->yaw)) * cos(deg2rad($this->pitch));
            $y = -sin(deg2rad($this->pitch));
            $z = cos(deg2rad($this->yaw)) * cos(deg2rad($this->pitch));
            $rod->setMotion(new Vector3($x, $y, $z));

            $event = new ProjectileLaunchEvent($rod);
            $event->call();

            if($event->isCancelled())
            {
                $rod->flagForDespawn();
                return;
            }

            $rod->spawnToAll();
            $this->getLevel()->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_THROW, 0, EntityIds::PLAYER);
            $this->fishing = $rod;
        }
    }

    /**
     * @param Item $item - The item to force a player.
     * @param bool $animate - Called to animate the player.
     * @return bool
     *
     * Called to force the player to use the rod.
     */
    public function useRod(Item $item, bool $animate): bool
    {
        if(!$this->spawned || !$this->isAlive() || $this->isSpectator() || $this->isFakeSpectating())
        {
            return false;
        }

        if($animate)
        {
            $this->sendAnimation(AnimatePacket::ACTION_SWING_ARM);
        }

        if($this->isFishing())
        {
            $this->stopFishing();

            if(!$this->isCreative())
            {
                // TODO: Account for unbreaking enchantments.
                $item->setDamage($item->getDamage() + 1);
                if($item->getDamage() > 65)
                {
                    $item = Item::get(Item::AIR);
                }
                $this->getInventory()->setItemInHand($item);
            }
            return true;
        }

        $this->startFishing();
        return true;
    }
}