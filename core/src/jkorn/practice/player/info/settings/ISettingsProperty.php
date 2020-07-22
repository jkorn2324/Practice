<?php

declare(strict_types=1);

namespace jkorn\practice\player\info\settings;


use jkorn\practice\player\misc\IPlayerProperty;

interface ISettingsProperty extends IPlayerProperty
{

    /**
     * @return string
     *
     * Gets the display from the option.
     */
    public function getDisplay(): string;
}