<?php

declare(strict_types=1);

namespace practice;


use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Server;
use practice\player\PracticePlayer;

class PracticeListener implements Listener
{

    /** @var PracticeCore */
    private $core;
    /** @var Server */
    private $server;

    public function __construct(PracticeCore $core)
    {
        $this->core = $core;
        $this->server = $core->getServer();

        $this->server->getPluginManager()->registerEvents($this, $core);
    }

    /**
     * @param PlayerCreationEvent $event
     *
     * Called when the player is instantiated.
     */
    public function onCreation(PlayerCreationEvent $event)
    {
        $event->setPlayerClass(PracticePlayer::class);
    }

    /**
     * @param PlayerJoinEvent $event
     *
     * Called when the player first joins the server.
     */
    public function onJoin(PlayerJoinEvent $event)
    {
        // TODO
    }
}