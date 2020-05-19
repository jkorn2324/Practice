<?php

declare(strict_types=1);

namespace practice\player;


use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\SourceInterface;
use pocketmine\Player;
use pocketmine\utils\UUID;
use practice\player\info\ClientInfo;
use practice\scoreboard\ScoreboardData;

class PracticePlayer extends Player
{

    /** @var ScoreboardData|null */
    protected $scoreboardData = null;
    /** @var bool */
    private $scoreboardEnabled = true;

    /** @var UUID - Allows for disguises. */
    protected $serverUUID;

    // The player's client information.

    /** @var string */
    protected $version = "";
    /** @var ClientInfo|null */
    protected $clientInfo = null;

    public function __construct(SourceInterface $interface, string $ip, int $port)
    {
        parent::__construct($interface, $ip, $port);
        $this->serverUUID = UUID::fromRandom();
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
        if(isset($data["scoreboardEnabled"])) {
            $this->scoreboardEnabled = (bool)$data["scoreboardEnabled"];
        }

        $type = $this->scoreboardEnabled ? ScoreboardData::SCOREBOARD_SPAWN : ScoreboardData::SCOREBOARD_NONE;
        $this->scoreboardData = new ScoreboardData($this, $type);
    }

    /**
     * Saves the data to the server.
     */
    public function saveData(): void
    {
        // TODO
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
}