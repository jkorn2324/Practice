<?php

declare(strict_types=1);

namespace jkorn\practice\level;


use pocketmine\math\Vector3;
use jkorn\practice\misc\ISaved;
use jkorn\practice\PracticeUtil;

class PositionArea implements ISaved, IArea
{
    /** @var Vector3|null */
    public $vertex1 = null, $vertex2 = null;

    /**
     * @return bool
     *
     * Determines if the position area is valid.
     */
    public function isValid(): bool
    {
        return $this->vertex1 !== null && $this->vertex2 !== null;
    }

    /**
     * @return Vector3|null
     *
     * Returns the very center position of the area.
     */
    public function getCenter(): ?Vector3
    {
        if($this->vertex1 === null || $this->vertex2 === null)
        {
            return null;
        }

        return new Vector3(
            ($this->vertex1->x + $this->vertex2->x) / 2,
            ($this->vertex1->y + $this->vertex2->y) / 2,
            ($this->vertex1->z + $this->vertex2->z) / 2
        );
    }

    /**
     * @param Vector3 $position
     * @return bool
     *
     * Determines whether or not the position is in the area.
     */
    public function isWithinArea(Vector3 $position): bool
    {
        $topVertex = $this->topVertex(); $bottomVertex = $this->bottomVertex();
        if(!$this->isValid())
        {
            return false;
        }

        return $position->x >= $bottomVertex->x && $position->x <= $topVertex->x
            && $position->y >= $bottomVertex->y && $position->y <= $topVertex->y
            && $position->z >= $bottomVertex->z && $position->z <= $topVertex->z;
    }

    /**
     * @return Vector3|null
     *
     * Gets the top vertex in the area.
     */
    public function topVertex(): ?Vector3
    {
        if(!$this->isValid())
        {
            return null;
        }

        $x = $this->vertex1->x > $this->vertex2->x ? $this->vertex1->x : $this->vertex2->x;
        $y = $this->vertex1->y > $this->vertex2->y ? $this->vertex1->y : $this->vertex2->y;
        $z = $this->vertex1->z > $this->vertex2->z ? $this->vertex1->z : $this->vertex2->z;

        return new Vector3($x, $y, $z);
    }

    /**
     * @return Vector3|null
     *
     * Gets the bottom vertex in the area.
     */
    public function bottomVertex(): ?Vector3
    {
        if(!$this->isValid())
        {
            return null;
        }

        $x = $this->vertex1->x < $this->vertex2->x ? $this->vertex1->x : $this->vertex2->x;
        $y = $this->vertex1->y < $this->vertex2->y ? $this->vertex1->y : $this->vertex2->y;
        $z = $this->vertex1->z < $this->vertex2->z ? $this->vertex1->z : $this->vertex2->z;

        return new Vector3($x, $y, $z);
    }

    /**
     * @return array
     *
     * Exports the position area to an array.
     */
    public function export(): array
    {
        return [
            "vertex1" => PracticeUtil::vec3ToArr($this->vertex1),
            "vertex2" => PracticeUtil::vec3ToArr($this->vertex2)
        ];
    }

    /**
     * @param array $data - The area data.
     * @return PositionArea
     *
     * Decodes the position area.
     */
    public static function decode(array $data)
    {
        $area = new PositionArea();

        if(isset($data["vertex1"], $data["vertex2"]))
        {
            $vertex1 = PracticeUtil::arrToVec3($data["vertex1"]);
            $vertex2 = PracticeUtil::arrToVec3($data["vertex2"]);

            $area->vertex1 = $vertex1;
            $area->vertex2 = $vertex2;
        }

        return $area;
    }
}