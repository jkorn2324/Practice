<?php

declare(strict_types=1);

namespace jkorn\practice\games\misc\managers;

use jkorn\practice\arenas\PracticeArenaManager;
use jkorn\practice\forms\display\FormDisplay;
use jkorn\practice\forms\types\properties\ButtonTexture;
use jkorn\practice\games\misc\leaderboards\IGameLeaderboard;
use jkorn\practice\games\misc\gametypes\IGame;
use jkorn\practice\items\PracticeItemManager;
use jkorn\practice\scoreboard\display\manager\AbstractScoreboardDisplayManager;
use pocketmine\Player;

interface IGameManager
{
    /**
     * Called when the game manager is first registered.
     */
    public function onRegistered(): void;

    /**
     * Called when the game manager is unregistered.
     */
    public function onUnregistered(): void;

    /**
     * Called when the game manager has been saved.
     */
    public function onSave(): void;

    /**
     * @param Player $player
     * @return IGame|null - Returns the game the player is playing, false otherwise.
     *
     * Gets the game from the player.
     */
    public function getFromPlayer(Player $player): ?IGame;

    /**
     * @return string
     *
     * Gets the type of game manager.
     */
    public function getType(): string;

    /**
     * @param $manager
     * @return bool
     *
     * Determines if one manager is equivalent to another.
     */
    public function equals($manager): bool;

    /**
     * @return int
     *
     * Gets the number of players playing.
     */
    public function getPlayersPlaying(): int;

    /**
     * @return PracticeItemManager|null
     *
     * Gets the item manager for the game manager.
     */
    public function getItemManager(): ?PracticeItemManager;

    /**
     * @return PracticeArenaManager|null
     *
     * Gets the game manager's arena manager, return null if the game
     * manager doesn't use an arena manager.
     */
    public function getArenaManager(): ?PracticeArenaManager;

    /**
     * @return IGameLeaderboard|null
     *
     * Gets the leaderboard of the game manager. Return null
     * if the game doesn't have a leaderboard.
     */
    public function getLeaderboard(): ?IGameLeaderboard;

    // ----------------------------------- FORM INFORMATION -----------------------------

    /**
     * @return string
     *
     * Gets the title of the type of game.
     */
    public function getDisplayName(): string;

    /**
     * @return ButtonTexture|null
     *
     * Gets the form button texture.
     */
    public function getFormButtonTexture(): ?ButtonTexture;

    /**
     * @param Player $player - The player that selected the game.
     *
     * Called when the game is selected in the Play Games Form.
     */
    public function onGameSelected(Player $player): void;
}