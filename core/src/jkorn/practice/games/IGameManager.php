<?php

declare(strict_types=1);

namespace jkorn\practice\games;

use jkorn\practice\forms\display\FormDisplay;
use jkorn\practice\games\misc\leaderboards\IGameLeaderboard;
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
     * @param mixed ...$args - The arguments needed to create a new game.
     *
     * The arguments needed to create a new
     */
    public function create(...$args): void;

    /**
     * @param Player $player
     * @return IGame|null - Returns the game the player is playing, false otherwise.
     *
     * Gets the game from the player.
     */
    public function getFromPlayer(Player $player): ?IGame;

    /**
     * @param $game
     *
     * Removes the game from the list.
     */
    public function remove($game): void;

    /**
     * Updates the game manager.
     * @param int $currentTick
     */
    public function update(int $currentTick): void;

    /**
     * @return string
     *
     * Gets the type of game manager.
     */
    public function getType(): string;

    /**
     * @return string
     *
     * Gets the title of the type of game.
     */
    public function getTitle(): string;

    /**
     * @return string
     *
     * Gets the texture of the game type, used for forms.
     */
    public function getTexture(): string;

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
     * @return FormDisplay|null
     *
     * Gets the corresponding form used to put the player in the game.
     */
    public function getGameSelector(): ?FormDisplay;

    /**
     * @return IGameLeaderboard|null
     *
     * Gets the leaderboard of the game manager. Return null
     * if the game doesn't have a leaderboard.
     */
    public function getLeaderboard(): ?IGameLeaderboard;
}