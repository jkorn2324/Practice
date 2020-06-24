<?php

declare(strict_types=1);

namespace practice\arenas;


use pocketmine\event\Event;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use practice\misc\ISaved;
use practice\level\PositionArea;
use practice\PracticeUtil;

abstract class PracticeArena implements ISaved
{
    const TYPE_FFA = 0;
    const TYPE_DUEL = 1;

    /** @var string */
    protected $name;
    /** @var Level */
    protected $level;

    /** @var string */
    protected $localizedName;

    /** @var PositionArea */
    protected $positionArea;

    public function __construct(string $name, Level $level, PositionArea $positionArea)
    {
        $this->name = $name;
        $this->level = $level;
        $this->localizedName = strtolower($name);
        $this->positionArea = $positionArea;
    }

    /**
     * @return string
     *
     * Gets the name of the practice arena.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Level
     *
     * Gets the level of the arena.
     */
    public function getLevel(): Level
    {
        return $this->level;
    }

    /**
     * @param Vector3 $vector3 - The position.
     * @return bool
     *
     * Determines if the position is within the arena.
     */
    public function isWithinArena(Vector3 $vector3): bool
    {
        if($vector3 instanceof Position)
        {
            if(!PracticeUtil::areLevelsEqual($this->level, $vector3->getLevel()))
            {
                return false;
            }
        }

        return $this->positionArea->isWithinArea($vector3);
    }
}