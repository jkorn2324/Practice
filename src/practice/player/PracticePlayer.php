<?php

declare(strict_types=1);

namespace practice\player;


use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\SourceInterface;
use pocketmine\Player;
use pocketmine\utils\UUID;
use practice\kits\Kit;
use practice\player\info\ClientInfo;
use practice\player\info\DisguiseInfo;
use practice\player\info\SettingsInfo;
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

    /** @var Kit|null */
    private $equippedKit = null;

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
     * @param $data
     *
     * Loads the data accordingly.
     */
    public function loadData($data): void
    {
        // Extracts the information from the data & initializes the settings.
        SettingsInfo::extract($data, $this->settingsInfo);

        $this->scoreboardData = new ScoreboardData($this, $this->settingsInfo->isScoreboardEnabled() ? ScoreboardData::SCOREBOARD_SPAWN_DEFAULT : ScoreboardData::SCOREBOARD_NONE);
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
            $this->settingsInfo->getHeader() => $this->settingsInfo->export()
        ];
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
     * Gets the server id of the player.
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
     * Clears the entire inventory of the player.
     */
    public function clearInventory(): void
    {
        $this->getInventory()->clearAll();
        $this->getArmorInventory()->clearAll();
    }
}