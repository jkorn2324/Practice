<?php

declare(strict_types=1);

namespace practice\arenas;


use practice\arenas\types\DuelArena;
use practice\arenas\types\FFAArena;
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
                $arena = FFAArena::decode($arenaName, $data);
                if($arena !== null)
                {
                    $this->arenas[$arena->getName()] = $arena;
                }
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
        $ffaFile = $this->arenaFolder . "ffa.json";
        $duelsFile = $this->arenaFolder . "duels.json";

        $ffaArenas = []; $duelArenas = [];

        foreach($this->arenas as $arena)
        {
            if($arena instanceof DuelArena)
            {
                $duelArenas[$arena->getName()] = $arena->export();
            }
            elseif ($arena instanceof FFAArena)
            {
                $ffaArenas[$arena->getName()] = $arena->export();
            }
        }

        file_put_contents($ffaFile, json_encode($ffaArenas));
        file_put_contents($duelsFile, json_encode($duelArenas));
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

    /**
     * @return array|FFAArena[]
     *
     * Gets the FFA Arenas in the list.
     */
    public function getFFAArenas()
    {
        $arenas = [];
        foreach($this->arenas as $arena)
        {
            if($arena instanceof FFAArena)
            {
                $arenas[] = $arena;
            }
        }
        return $arenas;
    }
}