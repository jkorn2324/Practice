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

    /**
     * @param string $arenaName
     * @param array $data
     * @return mixed
     *
     * Decodes the Practice arena abstractly.
     */
    abstract public static function decode(string $arenaName, array $data);
}