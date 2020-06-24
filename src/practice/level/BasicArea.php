<?php

declare(strict_types=1);

namespace practice\level;


use pocketmine\math\Vector3;
use practice\misc\ISaved;
use practice\PracticeUtil;

class BasicArea implements ISaved, IArea
{

    /** @var Vector3 */
    public $center;
    /** @var int */
    public $length = 5, $width = 5, $height = 10;

    public function __construct(Vector3 $center, int $length, int $width, int $height)
    {
        $this->length = $length;
        $this->width = $width;
        $this->height = $height;
        $this->center = $center;
    }

    /**
     * @param Vector3 $position
     * @return bool
     *
     * Determines whether or not the position is in an area.
     */
    public function isWithinArea(Vector3 $position): bool
    {
        $topVertex = new Vector3(
            $this->center->x + ((float)$this->width / 2),
            $this->center->y + ((float)$this->height),
            $this->center->z + ((float)$this->length / 2)
        );

        $bottomVertex = new Vector3(
            $this->center->x - ((float)$this->width / 2),
            $this->center->y + ((float)$this->height),
            $this->center->z + ((float)$this->length / 2)
        );

        return $position->x >= $bottomVertex->x && $position->x <= $topVertex->x
            && $position->y >= $bottomVertex->y && $position->y <= $topVertex->y
            && $position->z >= $bottomVertex->z && $position->z <= $topVertex->z;
    }

    /**
     * @return array
     *
     * Exports the area to an array.
     */
    public function export(): array
    {
        return [
            "length" => $this->length,
            "width" => $this->width,
            "height" => $this->height,
            "center" => PracticeUtil::vec3ToArr($this->center)
        ];
    }

    /**
     * @param array $data - The data.
     * @return BasicArea|null
     *
     * Decodes the area from an array of data.
     */
    public static function decode(array $data): ?BasicArea
    {
        if(isset($data["length"], $data["width"], $data["height"], $data["center"]))
        {
            $center = PracticeUtil::arrToVec3($data["center"]);
            if($center !== null)
            {
                return new BasicArea(
                    $center,
                    $data["length"],
                    $data["width"],
                    $data["height"]
                );
            }
        }
        return null;
    }
}