<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-03
 * Time: 18:52
 */

declare(strict_types=1);

namespace practice\game\inventory;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use practice\game\inventory\menus\inventories\PracBaseInv;
use practice\PracticeCore;

class InventoryTask extends Task
{

    private $player;

    private $inventory;

    public function __construct($player, PracBaseInv $inv) {
        if(PracticeCore::getPlayerHandler()->isPlayerOnline($player)) {
            $p = PracticeCore::getPlayerHandler()->getPlayer($player);
            $this->player = $p->getPlayerName();
            $this->inventory = $inv;
        }
    }

    /**
     * Actions to execute when run
     *
     * @param int $tick
     *
     * @return void
     */
    public function onRun(int $tick) {
        if(PracticeCore::getPlayerHandler()->isPlayerOnline($this->player)) {
            $p = PracticeCore::getPlayerHandler()->getPlayer($this->player);
            $this->inventory->onSendInvSuccess($p->getPlayer());
        } else {
            $this->inventory->onSendInvFail(Server::getInstance()->getPlayer($this->player));
        }
    }
}