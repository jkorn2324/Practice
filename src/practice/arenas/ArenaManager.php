<?php

declare(strict_types=1);

namespace practice\arenas;


use pocketmine\Server;
use practice\arenas\types\FFAArena;
use practice\misc\PracticeAsyncTask;
use practice\PracticeCore;
use practice\misc\AbstractManager;

class ArenaManager extends AbstractManager
{

    /** @var string */
    private $arenaFolder;

    /** @var PracticeArena[] */
    protected $arenas;

    public function __construct(PracticeCore $core)
    {
        $this->arenaFolder = $core->getDataFolder() . "arenas/";
        $this->arenas = [];

        parent::__construct($core, false);
    }

    /**
     * Loads the data needed for the manager.
     *
     * @param bool $async
     */
    protected function load(bool $async = false): void
    {
        if(!is_dir($this->arenaFolder))
        {
            mkdir($this->arenaFolder);
        }

        $ffaFile = $this->arenaFolder . "ffa.json";
        $duelsFile = $this->arenaFolder . "duels.json";

        if(!file_exists($duelsFile)) {
            $file = fopen($duelsFile, "w");
            fclose($file);
        }
        else
        {
            $contents = json_decode(file_get_contents($duelsFile), true);
            foreach($contents as $arenaName => $data)
            {
                // TODO: Decode duel arenas.
            }
        }

        if(!file_exists($ffaFile))
        {
            $file = fopen($ffaFile, "w");
            fclose($file);
        }
        else
        {
            $contents = json_decode(file_get_contents($ffaFile, true));
            foreach($contents as $arenaName => $data)
            {
                // TODO: Decode ffa arenas.
            }
        }
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

    /**
     * @param string $name
     * @return PracticeArena|null
     *
     * Gets the arena from the name.
     */
    public function getArena(string $name)
    {
        // TODO: Implement getArena() method.
        return null;
    }

    /**
     * @return int
     *
     * Gets the number of players playing in all FFA arenas.
     */
    public function getNumPlayersInFFA(): int
    {
        $output = 0;

        foreach($this->arenas as $arena)
        {
            if($arena instanceof FFAArena)
            {
                $output += $arena->getPlayers();
            }
        }
        return $output;
    }
}