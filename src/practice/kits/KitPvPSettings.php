<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-22
 * Time: 18:42
 */

declare(strict_types=1);

namespace practice\kits;


class KitPvPSettings {

    private $knockback;

    private $attack_delay;

    public function __construct(float $kb = 0.4, int $attack_delay = 10) {
        $this->knockback = $kb;
        $this->attack_delay = $attack_delay;
    }

    public function getAttackDelay() : int {
        return $this->attack_delay;
    }

    public function getKB() : float {
        return $this->knockback;
    }

    public function toMap() : array {
        return ["kb" => $this->getKB(), "attack-delay" => $this->attack_delay];
    }
}