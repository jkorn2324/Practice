<?php

declare(strict_types=1);

namespace jkorn\practice\arenas\types\ffa;

use jkorn\practice\display\DisplayStatistic;
use jkorn\practice\display\DisplayStatisticNames;
use jkorn\practice\kits\IKit;
use jkorn\practice\player\PracticePlayer;
use pocketmine\Player;
use pocketmine\Server;
use jkorn\practice\arenas\IArenaManager;
use jkorn\practice\PracticeCore;

class FFAArenaManager implements IArenaManager, DisplayStatisticNames
{
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
        $filePath = $arenaFolder . $this->getType() . ".json";
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
     * Used to register statistics that correspond with the manager.
     */
    public function onRegistered(): void
    {
        // Registers how many players are playing in the ffa arena.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_FFA_ARENA_PLAYERS_PLAYING,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof FFAArena)
                {
                    return $data->getPlayers();
                }
                elseif ($player instanceof PracticePlayer)
                {
                    $arena = $player->getFFAArena();
                    if($arena !== null)
                    {
                        return $arena->getPlayers();
                    }
                }
                return 0;
            }
        ));

        // Registers how many players are playing in the ffa arena.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_FFA_PLAYERS_PLAYING,
            function(Player $player, Server $server, $data)
            {
                $manager = PracticeCore::getBaseArenaManager()->getArenaManager("ffa");
                if($manager === null)
                {
                    return 0;
                }

                $arenas = $manager->getArenas();
                if(count($arenas) <= 0)
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

        // Gets the FFA arena name statistic.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_FFA_ARENA_NAME,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof FFAArena)
                {
                    return $data->getName();
                }
                elseif ($player instanceof PracticePlayer)
                {
                    $ffaArena = $player->getFFAArena();
                    if($ffaArena !== null)
                    {
                        return $ffaArena->getName();
                    }
                }

                return "Unknown";
            }
        ));

        // Gets the FFA Arena kit statistic.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_FFA_ARENA_KIT,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof FFAArena)
                {
                    $kit = $data->getKit();
                    if($kit !== null)
                    {
                        return $kit->getName();
                    }
                    return "None";
                }
                elseif ($data instanceof IKit)
                {
                    return $data->getName();
                }
                elseif ($player instanceof PracticePlayer)
                {
                    $ffaArena = $player->getFFAArena();
                    if($ffaArena !== null)
                    {
                        $kit = $ffaArena->getKit();
                        if($kit !== null)
                        {
                            return $kit->getName();
                        }
                    }
                }
                return "Unknown";
            }
        , false));
    }

    /**
     * Called when the arena manager is unregistered.
     * Called to unregister statistics.
     */
    public function onUnregistered(): void
    {
        DisplayStatistic::unregisterStatistic(self::STATISTIC_FFA_ARENA_PLAYERS_PLAYING);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_FFA_PLAYERS_PLAYING);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_FFA_ARENA_NAME);
        DisplayStatistic::unregisterStatistic(self::STATISTIC_FFA_ARENA_KIT);
    }

    /**
     * @param $manager
     * @return bool
     *
     * Determines if one manager is equivalent to another.
     */
    public function equals($manager): bool
    {
        return is_a($manager, __NAMESPACE__ . "\\" . self::class)
            && get_class($manager) === self::class;
    }
}