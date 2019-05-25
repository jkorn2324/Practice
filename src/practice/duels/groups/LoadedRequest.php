<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-29
 * Time: 15:54
 */

namespace practice\duels\groups;


class LoadedRequest
{

    private $queue;

    private $player;

    private $requested;

    public function __construct(string $player, string $requested)
    {
        $this->queue = null;

        $this->player = $player;

        $this->requested = $requested;
    }

    public function hasQueue() : bool {
        return !is_null($this->queue);
    }

    public function setQueue(string $queue) : void {
        $this->queue = $queue;
    }

    public function getRequestor() : string {
        return $this->player;
    }

    public function getRequested() : string {
        return $this->requested;
    }

    public function getQueue() : string {
        return $this->queue;
    }

    public function equals($object) : bool {
        $result = false;
        if($object instanceof LoadedRequest) {
            $result = $object->getRequested() === $this->requested and $object->getRequestor() === $this->player;
        }
        return $result;
    }
}