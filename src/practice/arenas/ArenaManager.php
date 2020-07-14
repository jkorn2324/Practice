<?php

declare(strict_types=1);

namespace practice\arenas;


use practice\arenas\types\DuelArena;
use practice\arenas\types\FFAArena;
use practice\misc\ISaved;
use practice\PracticeCore;
use practice\misc\AbstractManager;

class ArenaManager extends AbstractManager
{

    const ARENA_TYPE_ANY = "any";

    const ARENA_TYPE_FFA = "ffa";
    const ARENA_TYPE_DUELS = "duels";

    /** @var string */
    private $arenaFolder;

    /** @var array */
    protected $arenas;

    public function __construct(PracticeCore $core)
    {
        $this->arenaFolder = $core->getDataFolder() . "arenas/";
        $this->arenas = [];

        $this->registerDefaultArenaTypes();

        parent::__construct($core, false);
    }

    /**
     * Registers the default arena types.
     */
    private function registerDefaultArenaTypes(): void
    {
        // TODO:
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
            if(is_array($contents))
            {
                foreach($contents as $arenaName => $data)
                {
                    $arena = DuelArena::decode($arenaName, $data);
                    if($arena !== null)
                    {
                        $this->arenas[self::ARENA_TYPE_DUELS][$arena->getLocalizedName()] = $arena;
                    }
                }
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
            if(is_array($contents))
            {
                foreach($contents as $arenaName => $data)
                {
                    $arena = FFAArena::decode($arenaName, $data);
                    if($arena !== null)
                    {
                        $this->arenas[self::ARENA_TYPE_FFA][$arena->getLocalizedName()] = $arena;
                    }
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

        foreach($this->arenas as $type => $data)
        {
            $arenas = $this->exportArenas($data);

            switch($type)
            {
                case self::ARENA_TYPE_DUELS:
                    file_put_contents($duelsFile, json_encode($arenas));
                    break;
                case self::ARENA_TYPE_FFA:
                    file_put_contents($ffaFile, json_encode($arenas));
                    break;
            }
        }
    }

    /**
     * @param array $data
     * @return array
     *
     * Exports the duel arenas from the data.
     */
    private function exportArenas(array $data)
    {
        $output = [];

        foreach($data as $arena)
        {
            if($arena instanceof ISaved && $arena instanceof PracticeArena)
            {
                $output[$arena->getName()] = $arena->export();
            }
        }

        return $output;
    }

    /**
     * @param string &$name
     * @param string &$type - Determines the type we are looking for.
     * @return PracticeArena|null
     *
     * Gets the arena from the name.
     */
    public function getArena(string $name, string $type = self::ARENA_TYPE_ANY)
    {
        if($type !== self::ARENA_TYPE_ANY) {
            if (!isset($this->arenas[$type])) {
                return null;
            }
            $arenas = $this->arenas[$type];
            if (!isset($arenas[$localized = strtolower($name)])) {
                return null;
            }
            return $arenas[$localized];
        }

        // Runs a for loop to try and get the arena based on type (uses recursion)
        foreach($this->arenas as $arenaType => $data) {
            $output = $this->getArena($name, $arenaType);
            if($output !== null) {
                return $output;
            }
        }

        return null;
    }

    /**
     * @return int
     *
     * Gets the number of players playing in all FFA arenas.
     */
    public function getNumPlayersInFFA(): int
    {
        if(!isset($this->arenas[self::ARENA_TYPE_FFA]))
        {
            return 0;
        }

        $output = 0;
        $ffaArenas = $this->arenas[self::ARENA_TYPE_FFA];
        foreach($ffaArenas as $arena)
        {
            if($arena instanceof FFAArena)
            {
                $output += $arena->getPlayers();
            }
        }
        return $output;
    }

    /**
     * @param string $type
     * @return PracticeArena[]
     *
     * Gets the arenas based on type.
     */
    public function getArenas(string $type = self::ARENA_TYPE_ANY)
    {
        if($type !== self::ARENA_TYPE_ANY)
        {
            if(!isset($this->arenas[$type]))
            {
                return [];
            }

            return $this->arenas[$type];
        }

        return $this->arenas;
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