<?php

declare(strict_types=1);

namespace jkorn\practice\arenas\types\duels;

use jkorn\practice\level\gen\arenas\duels\DuelGeneratorInfo;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use jkorn\practice\arenas\PracticeArena;
use pocketmine\Server;

/**
 * Class PostGeneratedDuelArena
 * @package jkorn\practice\arenas\types\duels
 *
 * A duel arena that is post generated.
 */
class PostGeneratedDuelArena extends PracticeArena implements IDuelArena
{

    /** @var DuelGeneratorInfo */
    private $generatorInfo;

    /**
     * PostGeneratedDuelArena constructor.
     * @param string $levelName
     * @param DuelGeneratorInfo $info
     */
    public function __construct(string $levelName, DuelGeneratorInfo $info)
    {
        parent::__construct(strtolower($levelName), Server::getInstance()->getLevelByName($levelName));
        $this->generatorInfo = $info;
    }

    /**
     * @return Vector3
     *
     * Gets the first player position.
     */
    public function getP1StartPosition(): Vector3
    {
        $extraData = $this->generatorInfo->getExtraData();
        $sizeX = $extraData->arenaSizeX;
        $sizeZ = $extraData->arenaSizeZ;
        /** @var Vector3 $center */
        $center = $extraData->center;
        if($sizeX >= $sizeZ)
        {
            $center->z = $sizeZ - 4;
        }
        else
        {
            $center->x = $sizeX - 4;
        }
        return $center;
    }

    /**
     * @return Vector3
     *
     * Gets the second player position.
     */
    public function getP2StartPosition(): Vector3
    {
        $extraData = $this->generatorInfo->getExtraData();
        $sizeX = $extraData->arenaSizeX;
        $sizeZ = $extraData->arenaSizeZ;
        /** @var Vector3 $center */
        $center = $extraData->center;
        if($sizeX >= $sizeZ)
        {
            $center->z = 4;
        }
        else
        {
            $center->x = 4;
        }
        return $center;
    }

    /**
     * @param $arena
     * @return bool
     *
     * Determines if the arena is a post generated arena.
     */
    public function equals($arena): bool
    {
        if($arena instanceof PostGeneratedDuelArena)
        {
            return $arena->getLocalizedName() === $this->localizedName;
        }

        return false;
    }

    /**
     * @param Vector3 $position
     * @return bool
     *
     * Determines whether or not the player is in the arena.
     */
    public function isWithinArena(Vector3 $position): bool
    {
        return true;
    }
}