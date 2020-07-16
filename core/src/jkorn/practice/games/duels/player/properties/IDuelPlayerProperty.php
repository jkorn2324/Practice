<?php

declare(strict_types=1);

namespace jkorn\practice\games\duels\player\properties;


use pocketmine\item\Item;
use jkorn\practice\player\info\settings\ISettingsProperty;

interface IDuelPlayerProperty extends ISettingsProperty
{

    /**
     * @return Item
     *
     * Gets the corresponding item to represent in a inventory.
     * Used for Post-Match Inventories.
     */
    public function getItem(): Item;

    /**
     * @return string
     *
     * Converts the property to a string.
     */
    public function toString(): string;
}