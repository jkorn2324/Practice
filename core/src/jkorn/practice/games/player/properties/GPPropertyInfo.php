<?php

declare(strict_types=1);

namespace jkorn\practice\games\player\properties;


use pocketmine\item\Item;
use stdClass;

abstract class GPPropertyInfo
{

    /** @var string */
    protected $localizedName;
    /** @var string */
    private $class;
    /** @var stdClass */
    protected $propertyInformation;

    public function __construct(string $localizedName, $class, string $propertyDisplay = "")
    {
        $this->localizedName = $localizedName;
        $this->class = $class;

        $this->propertyInformation = new \stdClass();
        $this->addPropertyInfo("display", $propertyDisplay);
    }

    /**
     * @return string
     *
     * Gets the class.
     */
    protected function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return bool
     *
     * Determines if the Game Property Information is valid.
     */
    public function validate(): bool
    {
        return is_subclass_of($this->class, IGamePlayerProperty::class);
    }

    /**
     * @param string $key
     * @param $value
     *
     * Adds property information.
     */
    public function addPropertyInfo(string $key, $value): void
    {
        $this->propertyInformation->{$key} = $value;
    }

    /**
     * @return string
     *
     * Gets the property name as a string.
     */
    public function getPropertyLocalizedName(): string
    {
        return $this->localizedName;
    }

    /**
     * @param Item $item - the input item.
     * @param $defaultValue - The specific value.
     *
     * @return IGamePlayerProperty
     *
     * Converts the property Info to an instance.
     */
    abstract public function convertToInstance(Item &$item, $defaultValue = null);
}