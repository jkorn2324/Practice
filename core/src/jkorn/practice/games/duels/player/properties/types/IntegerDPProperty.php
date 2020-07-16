<?php

declare(strict_types=1);

namespace jkorn\practice\games\duels\player\properties\types;


use pocketmine\item\Item;
use jkorn\practice\games\duels\player\properties\IDuelPlayerProperty;
use jkorn\practice\player\info\settings\properties\IntegerSetting;

class IntegerDPProperty extends IntegerSetting implements IDuelPlayerProperty
{

    /** @var Item */
    private $item;
    /** @var string */
    private $propertyDisplay;
    /** @var string */
    private $description;

    public function __construct(string $localized, Item $item, string $display, string $description = "", int $value = 0)
    {
        parent::__construct($localized, $value);
        $this->item = $item;
        $this->propertyDisplay = $display;
        $this->description = $description;
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
        return str_replace("{VALUE}", $this->getValue(), $this->propertyDisplay);
    }
}