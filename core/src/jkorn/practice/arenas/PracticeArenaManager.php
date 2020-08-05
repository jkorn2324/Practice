<?php

declare(strict_types=1);

namespace jkorn\practice\arenas;


use jkorn\practice\forms\IPracticeForm;
use jkorn\practice\forms\types\properties\ButtonTexture;
use jkorn\practice\games\misc\managers\IGameManager;

abstract class PracticeArenaManager
{

    /** @var string */
    private $arenasDirectory;

    /** @var bool */
    private $loaded = false, $saved = false;

    /** @var IGameManager */
    private $parent;

    public function __construct(string $arenasDirectory, IGameManager $parent, bool $load)
    {
        $this->arenasDirectory = $arenasDirectory;
        $this->parent = $parent;

        if($load)
        {
            $this->load();
        }
    }

    /**
     * @return string
     *
     * Gets the directory where the arenas are stored.
     */
    public function getDirectory(): string
    {
        return $this->arenasDirectory;
    }

    /**
     * Literally loads the arenas from the manager.
     */
    public function load(): void
    {
        $this->onLoad();
        $this->loaded = true;
    }

    /**
     * Used to load the arenas of the arena manager.
     */
    abstract protected function onLoad(): void;

    /**
     * @return bool - Return true if loaded, false if not loaded.
     *
     * Called to determine whether the arenas have loaded.
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * @param $arena - The arena to add to the list.
     * @param bool $override
     *
     * @return bool - Determines whether or not the arena has been successfully added.
     *
     * Adds an arena to the practice arena manager.
     */
    abstract public function addArena($arena, bool $override = false): bool;

    /**
     * @param string $name - The name of the arena.
     * @return mixed
     *
     * Gets the arena from the arena list.
     */
    abstract public function getArena(string $name);

    /**
     * @param callable|null $filter - This filters the based on a callable,
     *        the callable should return a boolean and should contain an arena parameter.
     *
     * @return array
     *
     * Gets the arenas from the arena list.
     */
    abstract public function getArenas(?callable $filter = null);

    /**
     * @param $arena - The arena to delete from the list.
     *
     * Deletes the arena from the arena list.
     */
    abstract public function deleteArena($arena): void;

    /**
     * Used to save the arenas.
     */
    public function save(): void
    {
        if($this->loaded && !$this->saved)
        {
            $this->saved = $this->onSave();
        }
    }

    /**
     * Used to save the arenas.
     *
     * @return bool - Return true if the arenas have successfully been saved, false otherwise.
     */
    abstract protected function onSave(): bool;

    /**
     * @return bool
     *
     * Determines whether the arenas have been saved.
     */
    public function isSaved(): bool
    {
        return $this->saved;
    }

    /**
     * @return IGameManager
     *
     * Gets the parent game manager of the arena manager.
     */
    public function getGameManager()
    {
        return $this->parent;
    }

    // ---------------------------------- Form Information --------------------------------

    /**
     * @return string
     *
     * Gets the form display name.
     */
    public function getFormDisplayName(): string
    {
        return $this->parent->getDisplayName();
    }

    /**
     * @return ButtonTexture|null
     *
     * Gets the form display texture.
     */
    abstract public function getFormButtonTexture(): ?ButtonTexture;

    /**
     * @return IPracticeForm|null
     *
     * Gets the menu used to edit the arenas.
     */
    abstract public function getArenaEditorMenu(): ?IPracticeForm;

}