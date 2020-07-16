<?php

declare(strict_types=1);

namespace jkorn\practice\games\duels\player\properties;


use pocketmine\item\Item;

class PropertyInfo
{
    /** @var string */
    private $propertyName;
    /** @var string */
    private $propertyDisplay;

    /** @var string */
    private $class;

    /** @var string */
    private $description;

    public function __construct(string $name, string $propertyDisplay, $classType, string $description = "")
    {
        $this->propertyName = $name;
        $this->propertyDisplay = $propertyDisplay;

        $this->class = $classType;
        $this->description = $description;
    }

    /**
     * @return string
     * Gets the property name.
     */
    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    /**
     * @param Item $item - The input item.
     * @param mixed $defaultValue - The default value.
     * @return IDuelPlayerProperty
     *
     * Converts the property info to an instance.
     */
    public function convertToInstance(Item &$item, $defaultValue = null): IDuelPlayerProperty
    {
        $class = $this->class;
        return $defaultValue !== null ? new $class($this->propertyName, $item, $this->propertyDisplay, $this->description, $defaultValue) :
            new $class($this->propertyName, $item, $this->propertyDisplay, $this->description);
    }
}