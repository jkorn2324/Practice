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
    /** @var array */
    private $display;

    public function __construct(string $localized, array $display, int $value = 0)
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