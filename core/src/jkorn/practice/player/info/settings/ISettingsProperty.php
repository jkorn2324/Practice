<?php

declare(strict_types=1);

namespace jkorn\practice\player\info\settings;


interface ISettingsProperty
{

    /**
     * @return string
     *
     * Gets the localized name of the setting.
     */
    public function getLocalized(): string;

    /**
     * @return mixed
     *
     * Gets the value of the setting.
     */
    public function getValue();

    /**
     * @param $value
     * @return bool - Determines if value was changed.
     *
     * Sets the value of the setting.
     */
    public function setValue($value): bool;
}