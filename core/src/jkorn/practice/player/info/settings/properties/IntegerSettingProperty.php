<?php

declare(strict_types=1);

namespace jkorn\practice\player\info\settings\properties;


use jkorn\practice\player\info\settings\ISettingsProperty;

class IntegerSettingProperty implements ISettingsProperty
{

    /** @var int */
    private $value;
    /** @var string */
    private $localized;

    public function __construct(string $localized, int $value = 0)
    {
        $this->localized = $localized;
        $this->value = $value;
    }

    /**
     * @return string
     *
     * Gets the localized name of the setting.
     */
    public function getLocalized(): string
    {
        return $this->localized;
    }

    /**
     * @return int
     *
     * Gets the value of the setting.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param int $value
     * @return bool - Determines if value was changed.
     *
     * Sets the value of the setting.
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