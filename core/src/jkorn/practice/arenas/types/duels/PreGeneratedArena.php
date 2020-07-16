<?php

declare(strict_types=1);

namespace jkorn\practice\arenas\types\duels;


use pocketmine\level\Level;
use pocketmine\math\Vector3;
use jkorn\practice\arenas\PracticeArena;
use jkorn\practice\kits\Kit;
use jkorn\practice\level\PositionArea;
use jkorn\practice\misc\ISaved;
use jkorn\practice\PracticeCore;
use jkorn\practice\PracticeUtil;

/**
 * Class PreGeneratedArena
 * @package jkorn\practice\arenas\types\duels
 *
 * An arena that is pre-generated.
 */
class PreGeneratedArena extends PracticeArena implements IDuelArena, ISaved
{

    /** @var Vector3 */
    private $p1Position, $p2Position;
    /** @var PositionArea */
    private $arenaArea;

    /** @var array */
    private $arenaKits = [];

    /**
     * PreGeneratedArena constructor.
     * @param string $name
     * @param $kits
     * @param Vector3 $firstPosition
     * @param Vector3 $secondPosition
     * @param Level $level
     * @param PositionArea $area
     *
     * The constructor of the arena.
     */
    public function __construct(string $name, $kits, Vector3 $firstPosition, Vector3 $secondPosition, Level $level, PositionArea $area)
    {
        parent::__construct($name, $level);

        $this->arenaArea = $area;
        $this->p1Position = $firstPosition;
        $this->p2Position = $secondPosition;

        // Initializes the kits and adds them to the valid kits list.
        if(is_array($kits))
        {
            foreach($kits as $kit)
            {
                $iKit = PracticeCore::getKitManager()->get($kit);
                if($iKit !== null)
                {
                    $this->arenaKits[strtolower($iKit->getName())] = true;
                }
            }
        }
    }

    /**
     * @return Vector3
     *
     * Gets the first player position.
     */
    public function getP1StartPosition(): Vector3
    {
        return $this->p1Position;
    }

    /**
     * @return Vector3
     *
     * Gets the second player position.
     */
    public function getP2StartPosition(): Vector3
    {
        return $this->p2Position;
    }

    /**
     * @param $arena
     * @return bool
     *
     * Determines if two arenas are equivalent.
     */
    public function equals($arena): bool
    {
        if($arena instanceof PreGeneratedArena)
        {
            return $arena->getLocalizedName() === $this->getLocalizedName();
        }
        return false;
    }

    /**
     * @param $kit
     * @return bool
     *
     * Determines if the kit is valid.
     */
    public function isValidKit($kit): bool
    {
        if($kit instanceof Kit)
        {
            return isset($this->arenaKits[strtolower($kit->getName())]);
        }
        elseif (is_string($kit))
        {
            $iKit = PracticeCore::getKitManager()->get($kit);
            if($iKit !== null)
            {
                return $this->isValidKit($iKit);
            }
        }
        return false;
    }

    /**
     * @param $kit
     *
     * Adds a kit to the arena kit list.
     */
    public function addKit($kit): void
    {
        if($kit instanceof Kit)
        {
            $this->arenaKits[strtolower($kit->getName())] = true;
        }
        elseif (is_string($kit))
        {
            $iKit = PracticeCore::getKitManager()->get($kit);
            if($iKit !== null)
            {
                $this->addKit($iKit);
            }
        }
    }

    /**
     * @param $kit
     *
     * Removes the kit from the valid kit list.
     */
    public function removeKit($kit): void
    {
        if($kit instanceof Kit)
        {
            if(isset($this->arenaKits[$localized = strtolower($kit->getName())]))
            {
                unset($this->arenaKits[$localized]);
            }
        }
        elseif (is_string($kit))
        {
            if(isset($this->arenaKits[$localized = strtolower($kit)]))
            {
                unset($this->arenaKits[$localized]);
            }
        }
    }

    /**
     * @param Vector3 $position
     * @return bool
     *
     * Determines if the player is within the arena.
     */
    public function isWithinArena(Vector3 $position): bool
    {
        // TODO: fix.
        return false;
    }

    /**
     * @return array
     *
     * Exports the duel arena to an array.
     */
    public function export(): array
    {
        return [
            "area" => $this->arenaArea->export(),
            "kits" => array_values($this->arenaKits),
            "level" => $this->level->getName(),
            "p1Position" => PracticeUtil::arrToVec3($this->p1Position),
            "p2Position" => PracticeUtil::arrToVec3($this->p2Position)
        ];
    }

    /**
     * @param string $arenaName
     * @param $data
     * @return PreGeneratedArena|null
     *
     * Decodes the arena from the provided data & arena.
     */
    public static function decode(string $arenaName, array $data): ?PreGeneratedArena
    {
        // TODO: Decode the information from the data.
        return null;
    }
}