<?php

declare(strict_types=1);

namespace jkorn\practice\player\info\stats;


use jkorn\practice\player\misc\IPlayerProperty;

interface IStatProperty extends IPlayerProperty
{
    /**
     * @return bool
     *
     * Determines whether or not we want to save this statistic.
     */
    public function doSave(): bool;

    /**
     * @param bool $save
     *
     * Sets whether or not the statistic should be saved.
     */
    public function setSave(bool $save): void;
}