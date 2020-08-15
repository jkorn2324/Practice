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

    /** @var string */
    private $localizedName;

    public function __construct(Item $item, callable $callback)
    {
        $this->item = $item;
        $this->callback = $callback;
        $this->localizedName = "{$item->getId()}:{$item->getDamage()}";
    }

    /**
     * @param Player $player - The player who used the tap item.
     * @param Item $item - The tap item.
     * @param int $action - The action the player used when handling the item.
     *
     * @return bool - Determines whether or not the player successfully used the tap item.
     *
     * Called when the tap item is used.
     */
    public function onItemUse(Player $player, Item $item, int $action): bool
    {
        if(!$player->isOnline())
        {
            return false;
        }

        if($player instanceof PracticePlayer) {
            $settingsInfo = $player->getSettingsInfo();
            $tapItems = $settingsInfo->getProperty(SettingsInfo::TAP_ITEMS);
            if ($tapItems !== null) {
                $value = $tapItems->getValue();
                // Only return false if the player is a pe player and all these other checks are true.
                // Prevents windows 10 players from getting the same checks.
                if (!$value && $action !== PlayerInteractEvent::RIGHT_CLICK_AIR && $player->getClientInfo()->isPE()) {
                    return false;
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
            return $item->getId() === $this->item->getId();
        }

        return false;
    }

    /**
     * @return string
     *
     * Gets the item's localized name.
     */
    public function getLocalizedName(): string
    {
        return $this->localizedName;
    }
}