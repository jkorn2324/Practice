<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-14
 * Time: 15:41
 */

namespace practice\duels\misc;


class DuelPlayerHit
{

    private $hitter;

    private $tick;

    public function __construct(string $hitter, int $tick)
    {
        $this->tick = $tick;
        $this->hitter = $hitter;
    }

    public function getHitter() : string {
        return $this->hitter;
    }

    public function getTick() : int {
        return $this->tick;
    }

    public function equals($object) : bool {
        $result = false;
        if($object instanceof DuelPlayerHit) {
            $result = $this->tick === $object->getTick();
        }
        return $result;
    }

}