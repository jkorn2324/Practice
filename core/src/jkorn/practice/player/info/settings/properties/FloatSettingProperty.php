<?php

declare(strict_types=1);

namespace jkorn\practice\player\info\settings\properties;


use jkorn\practice\player\info\settings\ISettingsProperty;

class FloatSettingProperty implements ISettingsProperty
{

    /** @var float */
    private $value;
    /** @var string */
    private $localized;
    /** @var string[] */
    private $display;

    public function __construct(string $localized, array $display, float $value = 0.0)
    {
        $this->localized = $localized;
        $this->value = $value;
        $this->display = $display;
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
     * @return float
     *
     * Gets the value of the setting.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float $value
     * @return bool - Determines if value was changed.
     *
     * Sets the value of the setting.
     */
    public function setValue($value): bool
    {
        $oldValue = $this->value;
        $this->value = $value;
        return $oldValue !== $value;
    }

    /**
     * @return string
     *
     * Gets the display from the option.
     */
    public function getDisplay(): string
    {
        // TODO: Implement getDisplay() method.
        return "";
    }
}