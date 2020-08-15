<?php

declare(strict_types=1);

namespace jkorn\practice\player\misc;


use jkorn\practice\player\PracticePlayer;
use pocketmine\network\mcpe\PlayerNetworkSessionAdapter;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginException;
use pocketmine\Server;

class PracticePlayerSessionAdapter extends PlayerNetworkSessionAdapter
{

    /** @var PracticePlayer */
    private $player;
    /** @var Server */
    private $server;

    public function __construct(Server $server, Player $player)
    {
        parent::__construct($server, $player);

        if(!$player instanceof PracticePlayer)
        {
            throw new PluginException("Player for session adapter must be an instance of PracticePlayer.");
        }

        $this->server = $server;
        $this->player = $player;
    }

    /**
     * @param NetworkStackLatencyPacket $packet
     * @return bool
     * 
     * Called if the packet received is a network stack latency packet.
     */
    public function handleNetworkStackLatency(NetworkStackLatencyPacket $packet): bool
    {
        return $this->player->handleNetworkStackLatency($packet);
    }
}