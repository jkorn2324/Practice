<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-18
 * Time: 16:09
 */

namespace practice\player;


class PlayerClick
{

    public const CLICK_BLOCK = "block";
    public const CLICK_AIR = "air";

    private $tick;

    private $type;

    public function __construct(int $tick, string $type)
    {
        $this->tick = $tick;
        $this->type = $type;
    }

    public function getClickType() : string {
        return $this->type;
    }

    public function getTickClicked() : int {
        return $this->tick;
    }

    public function equals($object) : bool {
        $result = false;
        if($object instanceof PlayerClick){
            $t = $object->getTickClicked();
            $type = $object->getClickType();
            $result = $t === $this->tick and $type === $this->type;
        }
        return $result;
    }

    public function toString() : string {
        return "$this->type : $this->tick";
    }
}