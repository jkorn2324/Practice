<?php

declare(strict_types=1);

namespace jkorn\practice\misc;


use pocketmine\Server;
use jkorn\practice\PracticeCore;

abstract class AbstractManager
{

    /** @var Server */
    protected $server;

    public function __construct(bool $loadAsync)
    {
        $this->server = Server::getInstance();

        $this->load($loadAsync);
    }

    /**
     * Loads the data needed for the manager.
     *
     * @param bool $async
     */
    abstract protected function load(bool $async = false): void;

    /**
     * Saves the data from the manager.
     *
     * @param bool $async
    */
    abstract public function save(bool $async = false): void;
}