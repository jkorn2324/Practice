<?php

declare(strict_types=1);

namespace jkorn\practice\items;


use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class PracticeItem
{
    const TAG_PRACTICE_ITEM = "PracticeItem";
    const TAG_IS_PRACTICE = "IsPractice";

    /** @var string */
    private $localizedName, $displayName, $lore;
    /** @var Item */
    private $item;

    /** @var callable|null */
    private $callable = null;

    /** @var int */
    private $slot;

    public function __construct(string $localizedName, string $displayName, Item $item, int $slot, string $lore = "")
    {
        $this->localizedName = $localizedName;
        $this->displayName = $displayName;
        $this->lore = $lore;
        $this->item = $item;
        $this->slot = $slot % 9;
    }

    /**
     * @param callable $callable
     *
     * Sets the callable of the item.
     */
    public function setCallable(callable $callable): void
    {
        $this->callable = $callable;
    }

    /**
     * @return string
     *
     * Gets the localized name of the practice item.
     */
    public function getLocalized(): string
    {
        return $this->localizedName;
    }

    /**
     * @return Item
     *
     * Gets the item that is sent to the player.
     */
    public function getItem(): Item
    {
        $item = clone $this->item;
        $entry = $item->getNamedTagEntry(self::TAG_PRACTICE_ITEM);
        if(!$entry instanceof CompoundTag)
        {
            $entry = new CompoundTag(self::TAG_PRACTICE_ITEM);
        }
        $entry->setString(self::TAG_IS_PRACTICE, $this->localizedName);
        $item->setNamedTagEntry($entry);

        return $item->setCustomName($this->displayName)->setLore([$this->lore]);
    }

    /**
     * @param $item - The input item.
     * @return bool
     *
     * Determines if the item equals another item.
     */
    public function equals($item): bool
    {
        if($item instanceof PracticeItem)
        {
            return $item->getItem()->equals($this->getItem());
        }
        elseif ($item instanceof Item)
        {
            return $item->equals($this->getItem());
        }

        return false;
    }

    /**
     * @return int
     *
     * Gets the slot of the practice item.
     */
    public function getSlot(): int
    {
        return $this->slot;
    }

    /**
     * @param Player $player
     * @return bool
     *
     * Determines if the player clicked the practice item.
     */
    public function execute(Player $player): bool
    {
        $callable = $this->callable;
        if($callable !== null)
        {
            return $callable($player);
        }

        $player->sendMessage(TextFormat::RED . "[ERROR] The item '{$this->localizedName}' doesn't have any actions set up yet.");
        return true;
    }

    /**
     * @param string $localized
     * @param array $data
     * @return PracticeItem|null
     *
     * Decodes the PracticeItem based on the data.
     */
    public static function decode(string $localized, array $data): ?PracticeItem
    {
        if(isset($data["item.info"], $data["item.display"], $data["item.slot"]))
        {
            $info = $data["item.info"];
            if(isset($info["id"], $info["metadata"]))
            {
                $item = Item::get((int)$info["id"], (int)$info["metadata"]);
            }

            $display = $data["item.display"];
            if(isset($display["name"], $display["lore"]))
            {
                $name = (string)$display["name"];
                $lore = (string)$display["lore"];
            }

            $slot = (int)$data["item.slot"];

            if(isset($item, $name, $lore))
            {
                return new PracticeItem(
                    $localized,
                    $name,
                    $item,
                    $slot,
                    $lore
                );
            }
        }

        return null;
    }
}