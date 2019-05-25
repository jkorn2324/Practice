<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-15
 * Time: 20:01
 */

declare(strict_types=1);

namespace practice\player\info;


class PlayerCPSInfo
{

    private $tick;

    private $cps;

    public function __construct(int $tick, int $cps) {
        $this->tick = $tick;
        $this->cps = $cps;
    }

    public function getCPS() : int {
        return $this->cps;
    }

    public function getTick() : int {
        return $this->tick;
    }
}