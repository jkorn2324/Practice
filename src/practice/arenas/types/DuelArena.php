<?php

declare(strict_types=1);

namespace practice\arenas\types;


use pocketmine\level\Level;
use pocketmine\math\Vector3;
use practice\arenas\PracticeArena;
use practice\kits\Kit;
use practice\level\PositionArea;
use practice\misc\ISaved;

/**
 * Class DuelArena
 * @package practice\arenas\types
 *
 * Is a post-generated duel arena.
 */
class DuelArena extends PracticeArena implements ISaved
{

    /** @var array */
    protected $kits;

    /** @var Vector3 */
    protected $p1SpawnPosition;
    /** @var Vector3 */
    protected $p2SpawnPosition;

    /** @var PositionArea */
    private $arenaArea;

    public function __construct(string $name, Level $level, array $kits, Vector3 $p1SpawnPosition, Vector3 $p2SpawnPosition, PositionArea $area)
    {
        parent::__construct($name, $level);

        $this->p1SpawnPosition = $p1SpawnPosition;
        $this->p2SpawnPosition = $p2SpawnPosition;

        $this->arenaArea = $area;

        $this->kits = [];

        foreach($kits as $kit)
        {
            $this->kits[strtolower($kit)] = true;
        }
    }

    /**
     * @param Kit $kit
     *
     * Adds the kit to the list within the duel arena.
     */
    public function addKit(Kit $kit): void
    {
        if(!isset($this->kits[$local = strtolower($kit->getName())])) {
            $this->kits[$local] = true;
        }
    }

    /**
     * @param string $kit
     *
     * Removes a kit within the kit list.
     */
    public function removeKit(string $kit): void
    {
        if(isset($this->kits[$local = strtolower($kit)])) {
            unset($this->kits[$local]);
        }
    }

    /**
     * @param Kit $kit
     * @return bool
     *
     * Determines whether or not the kit is valid.
     */
    public function isValidKit(Kit $kit): bool
    {
        return isset($this->kits[strtolower($kit->getName())]);
    }

    /**
     * @return array
     *
     * Exports the duel arena to be stored.
     */
    public function export(): array
    {

        $kits = [];
        foreach($this->kits as $kit => $value) {
            $kits[] = $kit;
        }

        return [
            "level" => $this->getLevel()->getName(),
            "kits" => $kits,
            "area" => $this->arenaArea->export()
        ];
    }

    /**
     * @param $object
     * @return bool
     *
     * Determines if the two arenas are equivalent.
     */
    public function equals($object): bool
    {
        // TODO:
        return false;
    }

    /**
     * @param string $arenaName
     * @param $data
     * @return DuelArena|null
     *
     * Decodes the arena from the provided data & arena.
     */
    public static function decode(string $arenaName, array $data): ?DuelArena
    {
        // TODO: Decode the information from the data.
        return null;
    }

}