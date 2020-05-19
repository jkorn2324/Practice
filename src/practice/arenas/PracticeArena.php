<?php

declare(strict_types=1);

namespace practice\arenas;


use pocketmine\level\Level;
use practice\utils\ISaved;

abstract class PracticeArena implements ISaved
{

    /** @var string */
    protected $name;
    /** @var Level */
    protected $level;

    public function __construct(string $name, Level $level)
    {
        $this->name = $name;
        $this->level = $level;
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
}