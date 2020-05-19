<?php

declare(strict_types=1);

namespace practice\arenas;


use practice\PracticeCore;
use practice\utils\AbstractManager;

class ArenaManager extends AbstractManager
{

    /** @var string */
    private $file;

    public function __construct(PracticeCore $core)
    {
        parent::__construct($core);
        $this->file = $core->getDataFolder() . "Arenas.json";
    }

    /**
     * Loads the data needed for the manager.
     *
     * @param bool $async
     */
    protected function load(bool $async = false): void
    {
        if(!file_exists($this->file)) {
            $file = fopen($this->file, "w");
            fclose($file);
            return;
        }

        // TODO: Implement load() method.
    }

    /**
     * Saves the data from the manager.
     *
     * @param bool $async
     */
    public function save(bool $async = false): void
    {
        // TODO: Implement save() method.
    }
}