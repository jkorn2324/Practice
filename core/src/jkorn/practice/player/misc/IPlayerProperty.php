<?php

declare(strict_types=1);

namespace jkorn\practice\player\misc;


interface IPlayerProperty
{

    /**
     * @return string
     *
     * Gets the localized name of the property.
     */
    public function getLocalized(): string;

    /**
     * @return mixed
     *
     * Gets the value of the player property.
     */
    public function getValue();

    /**
     * @param $value
     * @return bool
     *
     * Sets the value of the player property.
     */
    public function setValue($value): bool;
}