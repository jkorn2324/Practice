<?php


namespace practice\games\duels\player\properties\types;


use pocketmine\item\Item;
use practice\games\duels\player\IDuelPlayerProperty;
use practice\player\info\settings\properties\FloatSetting;

class FloatDPProperty extends FloatSetting implements IDuelPlayerProperty
{

    /** @var string */
    private $item;
    /** @var string */
    private $description;
    /** @var string */
    private $display;

    public function __construct(string $localized, Item $item, string $display, string $description = "", float $value = 0.0)
    {
        parent::__construct($localized, $value);
        $this->item = $item;
        $this->description = $description;
        $this->display = $display;
    }

    /**
     * @return Item
     *
     * Gets the corresponding item to represent in a inventory.
     * Used for Post-Match Inventories.
     */
    public function getItem(): Item
    {
        $item = clone $this->item;
        return $item->setCustomName($this->toString())->setLore(explode("\n", $this->description));
    }

    /**
     * @return string
     *
     * Converts the property to a string.
     */
    public function toString(): string
    {
        return str_replace("{VALUE}", $this->getValue(), $this->display);
    }
}