<?php

declare(strict_types=1);

namespace jkorn\practice\level;


use pocketmine\math\Vector3;
use jkorn\practice\PracticeUtil;

class SpawnArea extends PositionArea
{

    /** @var Vector3 */
    private $spawnPosition;

    public function  __construct(Vector3 $spawnPosition)
    {
        $this->spawnPosition = $spawnPosition;
    }

    /**
     * @return Vector3
     *
     * Gets the spawn position.
     */
    public function getSpawn(): Vector3
    {
        return $this->spawnPosition;
    }

    /**
     * @param Vector3 $vector3
     *
     * Sets the spawn of the area.
     */
    public function setSpawn(Vector3 $vector3): void
    {
        $this->spawnPosition = $vector3;
    }

    /**
     * @return array
     *
     * Exports the spawn area.
     */
    public function export(): array
    {
        $output = parent::export();
        $output["spawn"] = PracticeUtil::vec3ToArr($this->spawnPosition);
        return $output;
    }

    /**
     * @param array $data
     * @return SpawnArea|null
     *
     * Decodes the spawn area.
     */
    public static function decode(array $data)
    {
        if(!isset($data["spawn"]))
        {
            return null;
        }

        $spawnPosition = PracticeUtil::arrToVec3($data["spawn"]);
        if($spawnPosition === null)
        {
            return null;
        }

        $area = new SpawnArea($spawnPosition);

        if(isset($data["vertex1"], $data["vertex2"]))
        {
            $area->vertex1 = PracticeUtil::arrToVec3($data["vertex1"]);
            $area->vertex2 = PracticeUtil::arrToVec3($data["vertex2"]);
        }

        return $area;
    }
}