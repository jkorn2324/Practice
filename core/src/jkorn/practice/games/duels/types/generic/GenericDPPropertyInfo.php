<?php

declare(strict_types=1);

namespace jkorn\practice\games\duels\types\generic;


use jkorn\practice\games\player\properties\GPPropertyInfo;
use jkorn\practice\games\player\properties\IGamePlayerProperty;
use pocketmine\item\Item;

class GenericDPPropertyInfo extends GPPropertyInfo
{
    /**
     * @param Item $item - the input item.
     * @param $defaultValue - The specific value.
     *
     * @return IGamePlayerProperty
     *
     * Converts the property Info to an instance.
     */
    public function convertToInstance(Item &$item, $defaultValue = null)
    {
        $class = $this->getClass();
        return $defaultValue !== null ? new $class($this->getPropertyLocalizedName(), $item, $this->propertyInformation, $defaultValue) :
            new $class($this->getPropertyLocalizedName(), $item, $this->propertyInformation);
    }
}