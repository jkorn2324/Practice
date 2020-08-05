<?php

declare(strict_types=1);

namespace jkorn\ffa\arenas;


use jkorn\ffa\FFAAddon;
use jkorn\ffa\FFAGameManager;
use jkorn\ffa\forms\internal\FFAInternalFormIDs;
use jkorn\practice\arenas\PracticeArenaManager;
use jkorn\practice\forms\internal\InternalForm;
use jkorn\practice\forms\IPracticeForm;
use jkorn\practice\forms\types\properties\ButtonTexture;

class FFAArenaManager extends PracticeArenaManager
{

    /** @var FFAArena[] */
    private $arenas;

    /** @var string */
    private $arenasFile;

    public function __construct(FFAAddon $addon, FFAGameManager $parent)
    {
        $this->arenas = [];

        parent::__construct($addon->getDataFolder(), $parent, false);

        // Stores the data folder.
        $this->arenasFile = $this->getDirectory() . "arenas.json";
    }

    /**
     * Used to load the arenas of the arena manager.
     */
    protected function onLoad(): void
    {
        if (!file_exists($this->arenasFile)) {
            $file = fopen($this->arenasFile, "w");
            fclose($file);
        } else {
            $contents = json_decode(file_get_contents($this->arenasFile), true);
            if (is_array($contents)) {
                foreach ($contents as $arenaName => $data) {
                    $arena = FFAArena::decode($arenaName, $data);
                    if ($arena !== null) {
                        $this->arenas[$arena->getLocalizedName()] = $arena;
                        $this->addGame($arena);
                    }
                }
            }
        }
    }

    /**
     * @param $arena - The arena to add to the list.
     * @param bool $override
     *
     * @return bool - Determines whether or not the arena has been successfully added.
     *
     * Adds an arena to the practice arena manager.
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
        $this->addGame($arena);
        return true;
    }

    /**
     * @param string $name - The name of the arena.
     * @return mixed
     *
     * Gets the arena from the arena list.
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
     * @param callable|null $filter - This filters the based on a callable,
     *        the callable should return a boolean and should contain an arena parameter.
     *
     * @return array|FFAArena[]
     *
     * Gets the arenas from the arena list.
     */
    public function getArenas(?callable $filter = null)
    {
        if($filter !== null)
        {
            return array_filter($this->arenas, $filter);
        }

        return $this->arenas;
    }

    /**
     * @param $arena - The arena to delete from the list.
     *
     * Deletes the arena from the arena list.
     */
    public function deleteArena($arena): void
    {
        if($arena instanceof FFAArena && $this->arenas[$arena->getLocalizedName()])
        {
            // Removes the game.
            $this->deleteGame($arena->getLocalizedName());
            unset($this->arenas[$arena->getLocalizedName()]);
        }
    }

    /**
     * @param FFAArena $arena
     *
     * Adds the game to the parent game manager.
     */
    private function addGame(FFAArena &$arena): void
    {
        /** @var FFAGameManager $gameManager */
        $gameManager = $this->getGameManager();
        $gameManager->createGame($arena);
    }

    /**
     * @param string $localized
     *
     * Deletes the game from the game manager.
     */
    private function deleteGame(string $localized): void
    {
        /** @var FFAGameManager $gameManager */
        $gameManager = $this->getGameManager();
        $gameManager->removeGame($localized);
    }

    /**
     * Used to save the arenas.
     *
     * @return bool - Return true if the arenas have successfully been saved, false otherwise.
     */
    protected function onSave(): bool
    {
        $exported = [];
        foreach($this->arenas as $arena)
        {
            $exported[$arena->getName()] = $arena->export();
        }
        file_put_contents($this->arenasFile, json_encode($exported));
        return true;
    }

    /**
     * @return ButtonTexture|null
     *
     * Gets the form display texture.
     */
    public function getFormButtonTexture(): ?ButtonTexture
    {
        // TODO: Implement getFormButtonTexture() method.
        return null;
    }

    /**
     * @return IPracticeForm|null
     *
     * Gets the menu used to edit the arenas.
     */
    public function getArenaEditorMenu(): ?IPracticeForm
    {
        return InternalForm::getForm(FFAInternalFormIDs::FFA_ARENA_MENU);
    }
}