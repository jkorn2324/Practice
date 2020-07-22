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
    /** @var string[] */
    private $display;

    public function __construct(string $settingLocalized, array $display, bool $value = true)
    {
        $this->localized = $settingLocalized;
        $this->value = $value;
        $this->display = $display;
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
        return $oldValue !== $value;
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
     * Gets the display from the option.
     */
    public function getDisplay(): string
    {
        if($this->value)
        {
            return $this->display["disabled"];
        }
        return $this->display["enabled"];
    }
}