<?php

declare(strict_types=1);

namespace jkorn\practice\games\player\properties;


use jkorn\practice\player\misc\IPlayerProperty;
use pocketmine\item\Item;

interface IGamePlayerProperty extends IPlayerProperty
{

    /**
     * @return string
     *
     * Gets the display property string.
     */
    public function display(): string;

    /**
     * @return Item
     *
     * Gets the display property as an item.
     */
    public function asItem(): Item;

    /**
     * @param $object
     * @return bool
     *
     * Determines if a property is equivalent to another.
     */
    public function equals($object): bool;
}