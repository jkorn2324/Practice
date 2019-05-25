<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-03
 * Time: 20:00
 */

declare(strict_types=1);

namespace practice\game\inventory\menus;

use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\Player;
use practice\game\inventory\menus\inventories\PracBaseInv;
use practice\player\PracticePlayer;

abstract class BaseMenu {

    private $edit;

    private $name;

    private $inv;

    public function __construct(PracBaseInv $inv) {
        $this->inv = $inv;
        $this->edit = true;
        $this->name = $inv->getName();
    }

    public function getInventory() : PracBaseInv {
        return $this->inv;
    }

    public function setName(string $name) : BaseMenu {
        $this->name = $name;
        return $this;
    }

    public function getName() : string {
        return $this->name;
    }

    public function setEdit(bool $edit) : BaseMenu {
        $this->edit = $edit;
        return $this;
    }

    public function canEdit() : bool {
        return $this->edit;
    }

    public function send(Player $player, ?string $customName = null) : bool {
        return $this->getInventory()->send($player, ($customName !== null ? $customName : $this->getName()));
    }

    abstract public function onItemMoved(PracticePlayer $p, SlotChangeAction $action) : void;

    abstract public function onInventoryClosed(Player $player) : void;

    abstract public function sendTo($player) : void;

}