<?php

declare(strict_types=1);

namespace jkorn\practice\arenas\types\duels;


use jkorn\practice\arenas\IArenaManager;
use jkorn\practice\PracticeCore;
use pocketmine\Server;

class DuelArenaManager implements IArenaManager
{
    /** @var PracticeCore */
    private $core;
    /** @var Server */
    private $server;

    /** @var PreGeneratedDuelArena[] */
    private $arenas;
    /** @var array */
    private $openArenas;
    /** @var bool */
    private $loaded = false;

    public function __construct(PracticeCore $core)
    {
        $this->core = $core;
        $this->server = $core->getServer();

        $this->arenas = [];
        $this->openArenas = [];
    }

    /**
     * Called when the arena manager is first registered.
     */
    public function onRegistered(): void
    {
        // TODO: Implement onRegistered() method.
    }

    /**
     * Called when the arena manager is unregistered.
     */
    public function onUnregistered(): void
    {
        // TODO: Implement onUnregistered() method.
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
        if(isset($this->arenas[strtolower($name)]))
        {
            return $this->arenas[strtolower($name)];
        }
        return null;
    }

    /**
     * @param PreGeneratedDuelArena $arena
     *
     * Closes the arena so it can't be used.
     */
    public function close(PreGeneratedDuelArena $arena): void
    {
        if(isset($this->openArenas[$arena->getLocalizedName()]))
        {
            unset($this->openArenas[$arena->getLocalizedName()]);
        }
    }

    /**
     * @param PreGeneratedDuelArena $arena
     *
     * Opens the arena so it can be used again.
     */
    public function open(PreGeneratedDuelArena $arena): void
    {
        $this->openArenas[$arena->getLocalizedName()] = true;
    }

    /**
     * @return PreGeneratedDuelArena|null
     *
     * Gets a random duel arena.
     */
    public function randomArena(): ?PreGeneratedDuelArena
    {
        if(count($this->openArenas) <= 0)
        {
            return null;
        }
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
     * @return array|PreGeneratedDuelArena[]
     *
     * Gets an array or list of arenas.
     */
    public function getArenas()
    {
        return $this->arenas;
    }

    /**
     * @return PreGeneratedDuelArena|null
     *
     * Gets a random open pre generated arena.
     */
    public function getRandomOpenArena(): ?PreGeneratedDuelArena
    {
        if(count($this->openArenas) <= 0)
        {
            return null;
        }

        $keys = array_keys($this->openArenas);
        $randomKey = $keys[mt_rand(0, count($keys) - 1)];
        if(isset($this->arenas[$randomKey]))
        {
            return $this->arenas[$randomKey];
        }
        return null;
    }

    /**
     * @return string
     *
     * Gets the arena manager type.
     */
    public function getType(): string
    {
        return "duels";
    }

    /**
     * @param string $arenaFolder
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
            foreach($contents as $arenaName => $data)
            {
                $arena = PreGeneratedDuelArena::decode($arenaName, $data);
                if($arena !== null)
                {
                    $this->arenas[$arena->getName()] = $arena;
                    $this->openArenas[$arena->getName()] = true;
                }
            }
        }

        $this->loaded = true;
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
     * @return array - The exported arena data.
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
     * @param $manager
     * @return bool
     *
     * Determines if one manager is equivalent to another.
     */
    public function equals($manager): bool
    {
        return is_a($manager, __NAMESPACE__ . "\\" . self::class, true)
            && get_class($manager) === self::class;
    }
}