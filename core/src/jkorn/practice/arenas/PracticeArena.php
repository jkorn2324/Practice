<?php

declare(strict_types=1);

namespace jkorn\practice\arenas;

use pocketmine\level\Level;

abstract class PracticeArena
{
    /** @var string */
    protected $name;
    /** @var Level */
    protected $level;

    /** @var string */
    protected $localizedName;

    public function __construct(string $name, Level $level)
    {
        $this->name = $name;
        $this->level = $level;
        $this->localizedName = strtolower($name);
    }

    /**
     * @return string
     *
     * Gets the localized name of the arena.
     */
    public function getLocalizedName(): string
    {
        return $this->localizedName;
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

    abstract public function equals($arena): bool;
}