<?php

declare(strict_types=1);

namespace jkorn\ffa\arenas;


use jkorn\ffa\FFAAddon;
use jkorn\ffa\FFAGameManager;
use jkorn\ffa\forms\internal\FFAInternalForms;
use jkorn\practice\arenas\IArenaManager;
use jkorn\practice\forms\internal\InternalForm;
use jkorn\practice\forms\IPracticeForm;
use pocketmine\Server;

class FFAArenaManager implements IArenaManager
{

    const MANAGER_TYPE = "ffa.manager";

    /** @var FFAAddon */
    private $core;
    /** @var FFAGameManager */
    private $manager;

    /** @var Server */
    private $server;

    /** @var bool */
    private $loaded = false;

    /** @var FFAArena[] */
    private $arenas = [];

    public function __construct(FFAAddon $core, FFAGameManager $manager)
    {
        $this->core = $core;
        $this->server = $core->getServer();

        $this->manager = $manager;
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
        if (!file_exists($filePath)) {
            $file = fopen($filePath, "w");
            fclose($file);
        } else {
            $contents = json_decode(file_get_contents($filePath), true);
            if (is_array($contents)) {
                foreach ($contents as $arenaName => $data) {
                    $arena = FFAArena::decode($arenaName, $data);
                    if ($arena !== null) {
                        $this->arenas[$arena->getLocalizedName()] = $arena;
                    }
                }
            }
        }

        // Loads the games from the ffa arenas.
        $this->manager->loadGames($this->arenas);
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
        foreach ($this->arenas as $arena) {
            $exported[$arena->getName()] = $arena->export();
        }
        return $exported;
    }

    /**
     * @param $arena
     * @param bool $override - Determines whether to override the arena.
     *
     * @return bool - Determines whether or not the arena has been successfully added.
     *
     * Adds an arena to the manager.
     */
    public function addArena($arena, bool $override = false): bool
    {
        if(!$arena instanceof FFAArena)
        {
            return false;
        }

        if(isset($this->arenas[$arena->getLocalizedName()]) && !$override)
        {
            return false;
        }

        $this->arenas[$arena->getLocalizedName()] = $arena;
        return true;
    }

    /**
     * @param string $name
     * @return mixed
     *
     * Gets an arena from its name.
     */
    public function getArena(string $name)
    {
        if (isset($this->arenas[$localized = strtolower($name)]))
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
        if($arena instanceof FFAArena && $this->arenas[$arena->getLocalizedName()])
        {
            unset($this->arenas[$arena->getLocalizedName()]);
        }
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
        return self::MANAGER_TYPE;
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
    public function onRegistered(): void {}

    /**
     * Called when the arena manager is unregistered.
     * Called to unregister statistics.
     */
    public function onUnregistered(): void {}

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

    /**
     * @return string
     *
     * Gets the display name of the arena manager,
     * used for the main form display.
     */
    public function getFormDisplayName(): string
    {
        return "FFA";
    }

    /**
     * @return string
     *
     * Gets the form texture for the main arena manager,
     * return "" for no texture.
     */
    public function getFormTexture(): string
    {
        // TODO: Get texture.
        return "";
    }

    /**
     * @return IPracticeForm|null
     *
     * Gets the arena editor selection menu.
     */
    public function getArenaEditorMenu(): ?IPracticeForm
    {
        return InternalForm::getForm(FFAInternalForms::FFA_ARENA_MENU);
    }
}