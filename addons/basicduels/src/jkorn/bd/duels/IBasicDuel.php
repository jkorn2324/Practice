<?php

declare(strict_types=1);

namespace jkorn\bd\duels;


use jkorn\bd\duels\types\BasicDuelGameInfo;
use jkorn\practice\games\misc\gametypes\ISpectatorGame;

interface IBasicDuel extends ISpectatorGame
{
    /**
     * @return int
     *
     * Gets the game's id.
     */
    public function getID(): int;

    /**
     * @param callable $callback - The callback used, requires a player parameter.
     *      Ex: broadcast(function(Player $player) {});
     *
     * Broadcasts something to everyone in the game based on a callback.
     */
    public function broadcastGlobal(callable $callback): void;

    /**
     * @return int
     *
     * Gets the number of players playing the duel in total.
     */
    public function getNumberOfPlayers(): int;

    /**
     * @return BasicDuelGameInfo
     *
     * Gets the game type of the duel.
     */
    public function getGameType(): BasicDuelGameInfo;

    /**
     * @return array
     *
     * Gets the results of the duel.
     */
    public function getResults(): array;
}