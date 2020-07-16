<?php

declare(strict_types=1);

namespace jkorn\practice\arenas;


use jkorn\practice\arenas\types\ffa\FFAArenaManager;
use jkorn\practice\misc\ISaved;
use jkorn\practice\PracticeCore;
use jkorn\practice\misc\AbstractManager;

class BaseArenaManager extends AbstractManager
{
    /** @var string */
    private $arenaFolder;

    /** @var PracticeArena[]|ISaved[] */
    protected $arenas;

    /** @var IArenaManager[] */
    private $arenaManagers;

    public function __construct(PracticeCore $core)
    {
        $this->arenaFolder = $core->getDataFolder() . "arenas/";
        $this->arenaManagers = [];

        $this->arenas = [];

        $this->registerDefaultManagers();

        parent::__construct($core, false);
    }

    /**
     * Registers the default arena types.
     */
    private function registerDefaultManagers(): void
    {
        $this->registerArenaManager(new FFAArenaManager($this->core));
    }

    /**
     * @param IArenaManager $manager - The arena manager.
     * @param bool $load - Loads the arena manager.
     * @param bool $override - Determine whether we want to override it.
     *
     * Registers the arena manager.
     */
    public function registerArenaManager(IArenaManager $manager, bool $load = false, bool $override = false): void
    {
        if(isset($this->arenaManagers[$manager->getType()]))
        {
            if(!$override)
            {
                return;
            }

            $previousManager = $this->arenaManagers[$manager->getType()];
            if($previousManager->equals($manager))
            {
                return;
            }

            if($previousManager->isLoaded())
            {
                $data = $previousManager->export();
                $file = $this->arenaFolder . $previousManager->getFile();
                file_put_contents($file, $data);
            }

            $previousManager->onUnregistered();
        }

        $this->arenaManagers[$manager->getType()] = $manager;
        $manager->onRegistered();

        if($load && !$manager->isLoaded())
        {
            $manager->load($this->arenaFolder, true);
        }
    }

    /**
     * @param string $manager
     * @param bool $save - Determines whether we want to save the arenas in there.
     *
     * Unregisters the arena manager.
     */
    public function unregisterArenaManager(string $manager, bool $save = true): void
    {
        if(isset($this->arenaManagers[$manager]))
        {
            $arenaManager = $this->arenaManagers[$manager];
            if($save && $arenaManager->isLoaded())
            {
                $data = $arenaManager->export();
                $file = $this->arenaFolder . $arenaManager->getFile();
                file_put_contents($file, json_encode($data));
            }

            $arenaManager->onUnregistered();
            unset($this->arenaManagers[$manager]);
        }
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

        foreach($this->arenaManagers as $manager)
        {
            if(!$manager->isLoaded())
            {
                $manager->load($this->arenaFolder, $async);
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
        foreach($this->arenaManagers as $manager)
        {
            $exportedData = $manager->export();
            $file = $this->arenaFolder . $manager->getType() . ".json";
            file_put_contents($file, json_encode($exportedData));
        }
    }

    /**
     * @param string $type
     * @return IArenaManager|null
     *
     * Gets an arena manager based on its type.
     */
    public function getArenaManager(string $type): ?IArenaManager
    {
        if(isset($this->arenaManagers[$type]))
        {
            return $this->arenaManagers[$type];
        }

        return null;
    }
}