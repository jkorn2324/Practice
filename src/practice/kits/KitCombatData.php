<?php

declare(strict_types=1);

namespace practice\kits;


use practice\misc\ISavedHeader;

class KitCombatData implements ISavedHeader
{
    const KIT_HEADER = "combat";

    /** @var double */
    protected $xz, $y;
    /** @var int */
    protected $speed;

    public function __construct($xz, $y, int $speed)
    {
        $this->xz = doubleval($xz);
        $this->y = doubleval($y);
        $this->speed = $speed;
    }

    /**
     * @return float
     *
     * Gets the xz knockback data.
     */
    public function getXZ(): float
    {
        return $this->xz;
    }

    /**
     * @return float
     *
     * Gets the y knockback data.
     */
    public function getY(): float
    {
        return $this->y;
    }

    /**
     * @return int
     *
     * Gets the attack speed.
     */
    public function getSpeed(): int
    {
        return $this->speed;
    }

    /**
     * @return array
     *
     * Exports the class to something
     * that could be saved.
     */
    public function export(): array
    {
        return [
            "xz" => $this->xz,
            "y" => $this->y,
            "speed" => $this->speed
        ];
    }

    /**
     * @return string
     *
     * Gets the header of the combat data,
     * used for saving.
     */
    public function getHeader()
    {
        return self::KIT_HEADER;
    }

    /**
     * @param $data
     * @return KitCombatData
     *
     * Decodes the kit combat data.
     */
    public static function decode($data): KitCombatData
    {
        if(isset($data["xz"], $data["y"], $data["speed"]))
        {
            return new KitCombatData($data["xz"], $data["y"], $data["speed"]);
        }

        return new KitCombatData(0.4, 0.4, 10);
    }
}