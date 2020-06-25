<?php

declare(strict_types=1);

namespace practice;


use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Server;
use practice\items\PracticeItem;
use practice\player\info\ClicksInfo;
use practice\player\info\ClientInfo;
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
    public function onJoin(PlayerJoinEvent $event): void
    {
        // TODO
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * Called when the player quits the game.
     */
    public function onQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();

        if ($player instanceof PracticePlayer) {
            $player->onLeave($event);
        }
    }

    /**
     * @param PlayerInteractEvent $event
     *
     * Called when the player interacts with an item.
     */
    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();

        if ($player !== null && $player instanceof PracticePlayer) {
            $clientInfo = $player->getClientInfo();
            $item = $event->getItem();

            if (($action = $event->getAction()) === PlayerInteractEvent::RIGHT_CLICK_BLOCK && $clientInfo instanceof ClientInfo && $clientInfo->isPE()) {
                $player->getClicksInfo()->addClick(true);
            }

            if (
                ($actionItem = PracticeCore::getItemManager()->getPracticeItem($item)) !== null
                && $actionItem instanceof PracticeItem
            ) {
                $event->setCancelled($actionItem->execute($player));
            }
            else
            {
                // TODO: Tap items.
            }
        }
    }
}