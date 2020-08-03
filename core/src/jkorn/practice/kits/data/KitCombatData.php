<?php

declare(strict_types=1);

namespace jkorn\practice\kits\data;


use jkorn\practice\misc\ISaved;

class KitCombatData implements ISaved
{
    const HORIZONTAL_KB = "xz";
    const VERTICAL_KB = "y";
    const ATTACK_DELAY = "speed";

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
     * @param string $key - The key of the combat data.
     * @param $value - The value of the combat data.
     * @return bool
     *
     * Updates the kit combat data based on key & value.
     */
    public function update(string $key, $value): bool
    {
        if(!is_numeric($value))
        {
            return false;
        }

        switch($key)
        {
            case self::HORIZONTAL_KB:
                $oldValue = $this->xz;
                $this->xz = floatval($value);
                break;
            case self::VERTICAL_KB:
                $oldValue = $this->y;
                $this->y = floatval($value);
                break;
            case self::ATTACK_DELAY:
                $oldValue = $this->speed;
                $this->speed = intval($value);
                break;
        }

        // Determines if the combat data value has updated.
        if(isset($oldValue))
        {
            return $oldValue !== $value;
        }

        return true;
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
            self::HORIZONTAL_KB => $this->xz,
            self::VERTICAL_KB => $this->y,
            self::ATTACK_DELAY => $this->speed
        ];
    }

    /**
     * @param $data
     * @return KitCombatData
     *
     * Decodes the kit combat data.
     */
    public static function decode($data): KitCombatData
    {
        if(isset($data[self::HORIZONTAL_KB], $data[self::VERTICAL_KB], $data[self::ATTACK_DELAY]))
        {
            return new KitCombatData($data[self::HORIZONTAL_KB], $data[self::VERTICAL_KB], $data[self::ATTACK_DELAY]);
        }

        return new KitCombatData(0.4, 0.4, 10);
    }
}