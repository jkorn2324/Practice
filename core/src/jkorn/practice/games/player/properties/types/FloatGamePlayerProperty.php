<?php

declare(strict_types=1);

namespace jkorn\practice\games\player\properties\types;


use jkorn\practice\games\player\properties\IGamePlayerProperty;
use pocketmine\item\Item;

class FloatGamePlayerProperty implements IGamePlayerProperty
{

    /** @var Item */
    private $item;
    /** @var string */
    private $localized;

    /** @var float */
    private $value;
    /** @var string */
    private $display, $description;

    public function __construct(string $localizedName, Item $item, \stdClass $information, float $value = 0.0)
    {
        $this->item = $item;
        $this->localized = $localizedName;
        $this->value = $value;
        $this->display = $information->display;
        $this->description = "";

        if(isset($information->description))
        {
            $this->description = $information->description;
        }
    }

    /**
     * @return string
     *
     * Gets the display property string.
     */
    public function display(): string
    {
        return str_replace("{VALUE}", $this->getValue(), $this->display);
    }

    /**
     * @return Item
     *
     * Gets the display property as an item.
     */
    public function asItem(): Item
    {
        $item = clone $this->item;
        return $item->setCustomName($this->display())->setLore(explode("\n", $this->description));
    }

    /**
     * @param $object
     * @return bool
     *
     * Determines if a property is equivalent to another.
     */
    public function equals($object): bool
    {
        if($object instanceof FloatGamePlayerProperty)
        {
            return $object->getLocalized() === $this->getLocalized();
        }

        return false;
    }

    /**
     * @return string
     *
     * Gets the localized name of the property.
     */
    public function getLocalized(): string
    {
        return $this->localized;
    }

    /**
     * @return mixed
     *
     * Gets the value of the player property.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float $value
     * @return bool
     *
     * Sets the value of the player property.
     */
    public function setValue($value): bool
    {
        $oldValue = $this->value;
        $this->value = $value;

        if($oldValue !== $value)
        {
            return true;
        }

        return false;
    }
}