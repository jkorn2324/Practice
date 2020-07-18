<?php

declare(strict_types=1);

namespace jkorn\practice\player\info\settings\properties;


use jkorn\practice\player\info\settings\ISettingsProperty;

class StringSettingProperty implements ISettingsProperty
{

    /** @var string */
    private $localized;
    /** @var string */
    private $value;

    public function __construct(string $localized, string $value = "")
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
     * @return string
     *
     * Gets the value of the setting.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return bool - Determines if value was changed.
     *
     * Sets the value of the setting.
     */
    public function setValue($value): bool
    {
        $oldValue = $this->value;
        $this->value = $oldValue;

        if($oldValue !== $value)
        {
            return true;
        }

        return false;
    }
}