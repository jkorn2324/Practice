<?php

declare(strict_types=1);

namespace jkorn\practice\arenas;


use jkorn\practice\forms\IPracticeForm;

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
     * @param bool $override - Determines whether to override the arena.
     *
     * @return bool - Determines whether or not the arena has been successfully added.
     *
     * Adds an arena to the manager.
     */
    public function addArena($arena, bool $override = false): bool;

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

    /**
     * @param $manager
     * @return bool
     *
     * Determines if one manager is equivalent to another.
     */
    public function equals($manager): bool;

    // ------------------------------ The Form Display Information ---------------------------

    /**
     * @return string
     *
     * Gets the display name of the arena manager,
     * used for the main form display.
     */
    public function getFormDisplayName(): string;

    /**
     * @return string
     *
     * Gets the form texture for the main arena manager,
     * return "" for no texture.
     */
    public function getFormTexture(): string;

    /**
     * @return IPracticeForm|null
     *
     * Gets the arena editor selection menu.
     */
    public function getArenaEditorMenu(): ?IPracticeForm;
}