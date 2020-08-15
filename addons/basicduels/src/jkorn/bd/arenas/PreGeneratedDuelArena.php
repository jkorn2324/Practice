<?php

declare(strict_types=1);

namespace jkorn\bd\arenas;


use jkorn\bd\BasicDuelsManager;
use jkorn\practice\arenas\PracticeArena;
use jkorn\practice\kits\IKit;
use jkorn\practice\level\PositionArea;
use jkorn\practice\misc\ISaved;
use jkorn\practice\PracticeCore;
use jkorn\practice\PracticeUtil;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Server;

class PreGeneratedDuelArena extends PracticeArena implements IDuelArena, ISaved
{

    /** @var Vector3 */
    private $p1Position, $p2Position;
    /** @var PositionArea */
    private $arenaArea;

    /** @var array */
    private $arenaKits = [];

    /** @var bool */
    private $visible;

    /**
     * PreGeneratedDuelArena constructor.
     * @param string $name
     * @param $kits
     * @param Vector3 $firstPosition
     * @param Vector3 $secondPosition
     * @param Level $level
     * @param PositionArea $area
     * @param bool $visible
     *
     * The constructor of the arena.
     */
    public function __construct(string $name, $kits, Vector3 $firstPosition, Vector3 $secondPosition, Level $level, PositionArea $area, bool $visible)
    {
        parent::__construct($name, $level);

        $this->arenaArea = $area;
        $this->p1Position = $firstPosition;
        $this->p2Position = $secondPosition;
        $this->visible = $visible;

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
     * @return bool
     *
     * Determines if the arena is visible.
     */
    public function isVisible(): bool
    {
        return $this->visible && count($this->arenaKits) > 0;
    }

    /**
     * @param bool $visible
     *
     * Sets the arena visibility.
     */
    public function setVisible(bool $visible): void
    {
        $this->visible = $visible;
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
        if($arena instanceof PreGeneratedDuelArena)
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
        if($kit instanceof IKit)
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
        if($kit instanceof IKit)
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
        if($kit instanceof IKit)
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
        return true;
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
            "visible" => $this->visible,
            "p1Position" => PracticeUtil::vec3ToArr($this->p1Position),
            "p2Position" => PracticeUtil::vec3ToArr($this->p2Position)
        ];
    }

    /**
     * @param string $arenaName
     * @param $data
     * @return PreGeneratedDuelArena|null
     *
     * Decodes the arena from the provided data & arena.
     */
    public static function decode(string $arenaName, $data): ?PreGeneratedDuelArena
    {
        $server = Server::getInstance();

        if(is_array($data) && isset($data["area"], $data["kits"], $data["level"], $data["visible"], $data["p1Position"], $data["p2Position"]))
        {
            $loaded = true;
            if(!$server->isLevelLoaded($data["level"]))
            {
                $loaded = $server->loadLevel($data["level"]);
            }

            // Checks if the level is loaded or not.
            if(!$loaded)
            {
                return null;
            }

            $level = $server->getLevelByName($data["level"]);
            $p1Position = PracticeUtil::arrToVec3($data["p1Position"]);
            $p2Position = PracticeUtil::arrToVec3($data["p2Position"]);

            if($level !== null && $p1Position !== null && $p2Position !== null)
            {
                return new PreGeneratedDuelArena(
                    $arenaName,
                    $data["kits"],
                    $p1Position,
                    $p2Position,
                    $level,
                    PositionArea::decode($data["area"]),
                    (bool)$data["visible"]
                );
            }
        }

        return null;
    }
}