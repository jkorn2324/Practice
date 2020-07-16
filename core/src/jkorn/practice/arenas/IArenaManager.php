<?php

declare(strict_types=1);

namespace jkorn\practice\arenas;


interface IArenaManager
{
    /**
     * Called when the arena manager is first registered.
     */
    public function onRegistered(): void;

    /**
     * Called when the arena manager is unregistered.
     */
    public function onUnregistered(): void;

    /**
     * @param $arena
     *
     * Adds an arena to the manager.
     */
    public function addArena($arena): void;

    /**
     * @param string $name
     * @return mixed
     *
     * Gets an arena from its name.
     */
    public function getArena(string $name);

    /**
     * @param $arena
     *
     * Deletes the arena from the list.
     */
    public function deleteArena($arena): void;

    /**
     * @return array
     *
     * Gets an array or list of arenas.
     */
    public function getArenas();

    /**
     * @return string
     *
     * Gets the exported file of the arena manager.
     */
    public function getFile(): string;

    /**
     * @return string
     *
     * Gets the arena manager type.
     */
    public function getType(): string;

    /**
     * @param string $arenaFolder
     * @param bool $async
     *
     * Loads the contents of the file and exports them as an arena.
     */
    public function load(string &$arenaFolder, bool $async): void;

    /**
     * @return bool
     *
     * Determines if the arena manager is loaded.
     */
    public function isLoaded(): bool;

    /**
     * @return array - The exported arena data.
     *
     * Exports the contents of the file.
     */
    public function export(): array;
}