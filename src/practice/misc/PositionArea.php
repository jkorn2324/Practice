<?php

declare(strict_types=1);

namespace practice\misc;


use pocketmine\math\Vector3;
use practice\PracticeUtil;

class PositionArea implements ISavedHeader
{

    /** @var string */
    protected $headerName = "area";

    /** @var Vector3 */
    public $vertex1, $vertex2;

    public function __construct(Vector3 $vertex1, Vector3 $vertex2, string $name = "area")
    {
        $this->headerName = $name;
        $this->vertex1 = $vertex1;
        $this->vertex2 = $vertex2;
    }

    /**
     * @return Vector3
     *
     * Returns the very center position of the area.
     */
    public function getCenter(): Vector3
    {
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
        $topVertex = $this->topVertex(); $bottomVertex = $this->bottomEdge();
        return $position->x >= $bottomVertex->x && $position->x <= $topVertex->x
            && $position->y >= $bottomVertex->y && $position->y <= $topVertex->y
            && $position->z >= $bottomVertex->z && $position->z <= $topVertex->z;
    }

    /**
     * @return Vector3
     *
     * Gets the top vertex in the area.
     */
    public function topVertex(): Vector3
    {
        $x = $this->vertex1->x > $this->vertex2->x ? $this->vertex1->x : $this->vertex2->x;
        $y = $this->vertex1->y > $this->vertex2->y ? $this->vertex1->y : $this->vertex2->y;
        $z = $this->vertex1->z > $this->vertex2->z ? $this->vertex1->z : $this->vertex2->z;

        return new Vector3($x, $y, $z);
    }

    /**
     * @return Vector3
     *
     * Gets the bottom vertex in the area.
     */
    public function bottomEdge(): Vector3
    {
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
     * Gets the header of the variable being saved.
     * @return string - The header.
     */
    public function getHeader()
    {
        return $this->headerName;
    }

    /**
     * @param string $name - The area name.
     * @param array $data - The area data.
     * @return PositionArea|null
     *
     * Decodes the position area.
     */
    public static function decode(string $name, array $data): ?PositionArea
    {
        // TODO: Implement function.
        return null;
    }
}