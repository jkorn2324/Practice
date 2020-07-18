<?php

declare(strict_types=1);

namespace jkorn\practice\player\info\settings\properties;


use jkorn\practice\player\info\settings\ISettingsProperty;

class BooleanSettingProperty implements ISettingsProperty
{

    /** @var bool */
    private $value;
    /** @var string */
    private $localized;

    public function __construct(string $settingLocalized, bool $value = true)
    {
        $this->localized = $settingLocalized;
        $this->value = $value;
    }

    /**
     * @return bool
     *
     * Gets the value of the setting.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param bool $value
     * @return bool - Determine if the value changed.
     *
     * Sets the value of the setting.
     */
    public function setValue($value): bool
    {
        $oldValue = $this->value;
        $this->value = (bool)$value;

        if($oldValue !== $value)
        {
            return true;
        }

        return false;
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
}