<?php

declare(strict_types=1);

namespace jkorn\practice\items;


use jkorn\practice\player\info\settings\SettingsInfo;
use jkorn\practice\player\PracticePlayer;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Item;
use pocketmine\Player;

class TapItem
{

    /** @var Item */
    private $item;

    /** @var callable */
    private $callback;

    /** @var int */
    private $itemID;

    /** @var bool */
    private $includeMeta;

    public function __construct(Item $item, callable $callback, bool $includeMeta)
    {
        $this->item = $item;
        $this->callback = $callback;
        $this->includeMeta = $includeMeta;
        $this->itemID = $item->getId();
    }

    /**
     * @param Player $player - The player who used the tap item.
     * @param Item $item - The tap item.
     * @param int $action - The action the player used when handling the item.
     *
     * @return bool - Determines whether to cancel the interact event (always true).
     *
     * Called when the tap item is used.
     */
    public function onItemUse(Player $player, Item $item, int $action): bool
    {
        if(!$player->isOnline())
        {
            return true;
        }

        if($player instanceof PracticePlayer) {
            $settingsInfo = $player->getSettingsInfo();
            $tapItems = $settingsInfo->getProperty(SettingsInfo::TAP_ITEMS);
            if ($tapItems !== null) {
                $value = $tapItems->getValue();
                // Only return false if the player is a pe player and all these other checks are true.
                // Prevents windows 10 players from getting the same checks.
                if (!$value && $action !== PlayerInteractEvent::RIGHT_CLICK_AIR && $player->getClientInfo()->isPE()) {
                    return true;
                }
            }
        }
        return ($this->callback)($player, $item, $action);
    }

    /**
     * @param $item - The input item.
     * @return bool
     *
     * Determines if the items are equivalent.
     */
    public function equalsItem($item): bool
    {
        if($item instanceof TapItem)
        {
            return $this->equalsItem($item->item);
        }
        elseif ($item instanceof Item)
        {
            if(!$this->includeMeta) {
                return $item->getId() === $this->item->getId();
            }

            return $item->getId() === $this->item->getId() && $item->getDamage() === $this->item->getDamage();
        }

        return false;
    }

    /**
     * @return int
     *
     * Gets the item's unique identifier.
     */
    public function getItemID(): int
    {
        return $this->itemID;
    }
}