<?php

declare(strict_types=1);

namespace jkorn\practice\level;


use pocketmine\math\Vector3;

interface IArea
{

    const AREA_HEADER = "area";

    /**
     * @param Vector3 $position
     * @return bool
     *
     * Determines whether or not the position is in an area.
     */
    public function isWithinArea(Vector3 $position): bool;

}