<?php

declare(strict_types=1);

namespace jkorn\practice\arenas\types\ffa;


use pocketmine\Player;
use pocketmine\Server;
use jkorn\practice\arenas\IArenaManager;
use jkorn\practice\PracticeCore;
use jkorn\practice\scoreboard\display\statistics\ScoreboardStatistic;

class FFAArenaManager implements IArenaManager
{

    const STATISTIC_IN_FFA = "ffa.stat.players";
    const STATISTIC_FFA_ARENA = "ffa.stat.arena";
    const STATISTIC_FFA_ARENA_PLAYERS = "ffa.stat.arena.players";

    /** @var PracticeCore */
    private $core;
    /** @var Server */
    private $server;

    /** @var bool */
    private $loaded = false;

    /** @var FFAArena[] */
    private $arenas = [];

    public function __construct(PracticeCore $core)
    {
        $this->core = $core;
        $this->server = $core->getServer();
    }

    /**
     * @param $arenaFolder
     * @param bool $async
     *
     * Loads the contents of the file and exports them as an arena.
     */
    public function load(string &$arenaFolder, bool $async): void
    {
        $filePath = $arenaFolder . $this->getFile();
        if(!file_exists($filePath))
        {
            $file = fopen($filePath, "w");
            fclose($file);
        }
        else
        {
            $contents = json_decode(file_get_contents($filePath), true);
            if(is_array($contents))
            {
                foreach($contents as $arenaName => $data)
                {
                    $arena = FFAArena::decode($arenaName, $data);
                    if($arena !== null)
                    {
                        $this->arenas[$arena->getLocalizedName()] = $arena;
                    }
                }
            }
        }

        $this->loaded = true;
    }

    /**
     * @return array
     *
     * Exports the contents of the file.
     */
    public function export(): array
    {
        $exported = [];
        foreach($this->arenas as $arena)
        {
            $exported[$arena->getName()] = $arena->export();
        }
        return $exported;
    }

    /**
     * @param $arena
     *
     * Adds an arena to the manager.
     */
    public function addArena($arena): void
    {
        // TODO: Implement addArena() method.
    }

    /**
     * @param string $name
     * @return mixed
     *
     * Gets an arena from its name.
     */
    public function getArena(string $name)
    {
        if(isset($this->arenas[$localized = strtolower($name)]))
        {
            return $this->arenas[$localized];
        }

        return null;
    }

    /**
     * @param $arena
     *
     * Deletes the arena from the list.
     */
    public function deleteArena($arena): void
    {
        // TODO: Implement deleteArena() method.
    }

    /**
     * @return array|FFAArena[]
     *
     * Gets an array or list of arenas.
     */
    public function getArenas()
    {
        return $this->arenas;
    }

    /**
     * @return string
     *
     * Gets the exported file of the arena manager.
     */
    public function getFile(): string
    {
        return "ffa.json";
    }

    /**
     * @return string
     *
     * Gets the arena manager type.
     */
    public function getType(): string
    {
        return "ffa";
    }

    /**
     * @return bool
     *
     * Determines if the arena manager is loaded.
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * Called when the arena manager is first registered.
     */
    public function onRegistered(): void
    {
        // TODO: Register the corresponding statistics.

        // Registers the number of players in an FFA arena.
        ScoreboardStatistic::registerStatistic(new ScoreboardStatistic(
            self::STATISTIC_IN_FFA,
            function(Player $player, Server $server)
            {
                $manager = PracticeCore::getArenaManager()->getArenaManager("ffa");
                if($manager === null)
                {
                    return 0;
                }

                $arenas = $manager->getArenas();
                if(count($arenas) <= 0)
                {
                    return 0;
                }

                $numPlayers = 0;
                foreach($arenas as $arena)
                {
                    if($arena instanceof FFAArena)
                    {
                        $numPlayers += $arena->getPlayers();
                    }
                }
                return $numPlayers;
            }
        ));
    }

    /**
     * Called when the arena manager is unregistered.
     */
    public function onUnregistered(): void
    {
        // TODO: Implement onUnregistered() method.
        ScoreboardStatistic::unregisterStatistic(self::STATISTIC_FFA_ARENA_PLAYERS);
    }
}