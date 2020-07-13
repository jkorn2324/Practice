<?php

declare(strict_types=1);

namespace practice\player;


use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\SourceInterface;
use pocketmine\Player;
use pocketmine\utils\UUID;
use practice\arenas\types\FFAArena;
use practice\data\PracticeDataManager;
use practice\items\ItemManager;
use practice\kits\Kit;
use practice\player\info\ActionsInfo;
use practice\player\info\ClicksInfo;
use practice\player\info\ClientInfo;
use practice\player\info\CombatInfo;
use practice\player\info\DisguiseInfo;
use practice\player\info\settings\SettingsInfo;
use practice\player\info\StatsInfo;
use practice\PracticeCore;
use practice\PracticeUtil;
use practice\scoreboard\ScoreboardData;

class PracticePlayer extends Player
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

    /** @var Kit|null */
    private $equippedKit = null;
    /** @var FFAArena|null */
    private $ffaArena = null;

    /** @var bool */
    private $doSave = true;

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
     * @param InventoryTransactionPacket $packet
     * @return bool
     *
     * Called when the player handles an inventory transaction.
     */
    public function handleInventoryTransaction(InventoryTransactionPacket $packet): bool
    {
        if(parent::handleInventoryTransaction($packet))
        {
            if(!$this->isOnline())
            {
                return true;
            }

            if($packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM || $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY)
            {
                $this->onClick(false);
            }

            return true;
        }
        return false;
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

        // Enables the player's disguise.
        if(isset($data["disguised"]) && (bool)$data["disguised"])
        {
            $this->enableDisguise();
        }

        $property = $this->settingsInfo->getProperty(SettingsInfo::SCOREBOARD_DISPLAY);
        $this->scoreboardData = new ScoreboardData($this, $property->getValue() ? ScoreboardData::SCOREBOARD_SPAWN_DEFAULT : ScoreboardData::SCOREBOARD_NONE);
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
        $this->putInLobby(false);
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * Called when the current player leaves the game.
     */
    public function onLeave(PlayerQuitEvent &$event): void
    {
        // TODO: Remove from queue, leave duel, etc...

        if($this->ffaArena !== null)
        {
            $this->ffaArena->removePlayer();
        }

        $dataProvider = PracticeDataManager::getDataProvider();
        // Saves the player as non async if server isn't running, etc...
        $dataProvider->savePlayer($this, !$this->getServer()->isRunning());
    }

    /**
     * @param int $currentTick
     * @return bool
     *
     * Called to update the player's information.
     */
    public function onUpdate(int $currentTick): bool
    {
        if(parent::onUpdate($currentTick))
        {
            $tickDiff = $currentTick - $this->lastUpdate;
            if($this->loggedIn && $this->spawned && $this->isAlive() && $tickDiff >= 1)
            {
                $this->updateInfo($currentTick);
            }

            return true;
        }

        return false;
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
     * @param bool $clickedBlock
     *
     * Called when the player clicks.
     */
    protected function onClick(bool $clickedBlock): void
    {
        $this->clicksInfo->addClick($clickedBlock);
    }

    /**
     * @param $player
     * @return bool
     *
     * Determines if the players are equivalent.
     */
    public function equalsPlayer($player): bool
    {
        if($player instanceof PracticePlayer)
        {
            return $player->serverUUID->equals($this->serverUUID);
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
     * @param Kit $kit
     *
     * Sets the player as equipped with a kit.
     */
    public function setEquipped(Kit $kit): void
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
     * @return Kit|null
     *
     * Gets the equipped kit for the player.
     */
    public function getEquippedKit(): ?Kit
    {
        return $this->equippedKit;
    }

    /**
     * @param FFAArena $arena
     *
     * Sets the player in an FFA arena.
     */
    public function setInFFA(FFAArena $arena): void
    {
        // TODO: Check if player was in a queue and remove them.
        // TODO: Check if player is in a duel.

        if(!$this->isInLobby())
        {
            // TODO: Message saying player isn't in the lobby.
            return;
        }

        $this->ffaArena = $arena;
        $arena->teleportTo($this, true);
    }

    /**
     * @return bool
     *
     * Sets the player in an FFA arena.
     */
    public function isInFFA(): bool
    {
        return $this->ffaArena !== null;
    }

    /**
     * @return FFAArena|null
     *
     * Gets the current ffa arena that the player
     * is in.
     */
    public function getFFAArena(): ?FFAArena
    {
        return $this->ffaArena;
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
        if(!$this->canBeDamaged())
        {
            $event->setCancelled(true);
            return;
        }

        parent::attack($event);

        $this->onPostAttack($event);
    }

    /**
     * @return bool
     *
     * Determines if the player can be damaged.
     */
    public function canBeDamaged(): bool
    {
        if($this->isCreative() || $this->isSpectator())
        {
            return false;
        }

        if($this->isInFFA())
        {
            return !$this->ffaArena->isWithinSpawn($this);
        }

        if($this->isInLobby())
        {

            return false;
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

            if($damager->isInFFA() && $damager->getFFAArena()->equals($this->ffaArena) && $event->getCause() !== EntityDamageEvent::CAUSE_SUICIDE)
            {
                $damager->getCombatInfo()->setInCombat(true);
                $this->getCombatInfo()->setInCombat(true);
            }

            // TODO: Update Duel storage.
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
     *
     * Puts the player in the lobby.
     */
    public function putInLobby(bool $teleport): void
    {
        // TODO: Add more things.

        // Removes all of the effects.
        $this->removeAllEffects();

        PracticeCore::getItemManager()->sendItemsFromType(ItemManager::TYPE_LOBBY, $this);
    }
}