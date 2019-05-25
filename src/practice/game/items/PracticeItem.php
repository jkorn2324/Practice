<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-19
 * Time: 11:17
 */

declare(strict_types=1);

namespace practice\game\items;

use pocketmine\item\Item;

class PracticeItem
{
    
    private $localizedName;

    private $slot;

    private $item;

    private $itemName;
    
    private $onlyExecuteInLobby;

    public function __construct(string $name, int $slot, Item $item, bool $exec = true)
    {
        $this->localizedName = $name;
        $this->slot = $slot;
        $this->item = $item;
        $this->itemName = $item->getName();
        $this->onlyExecuteInLobby = $exec;
    }

    public function setItem(Item $item) : self {
        $this->item = $item;
        $this->itemName = $item->getName();
        return $this;
    }

    public function canOnlyUseInLobby() : bool {
        return $this->onlyExecuteInLobby;
    }

    public function getItem() : Item {
        return $this->item;
    }

    public function getName() : string {
        return $this->itemName;
    }

    public function getLocalizedName() : string {
        return $this->localizedName;
    }

    public function getSlot() : int {
        return $this->slot;
    }
}