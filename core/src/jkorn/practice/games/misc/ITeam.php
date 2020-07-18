<?php

declare(strict_types=1);

namespace jkorn\practice\games\misc;


use pocketmine\Player;

interface ITeam
{

    /**
     * @param mixed ...$extraData
     *
     * Puts the players in a game.
     */
    public function putPlayersInGame(...$extraData): void;

    /**
     * @return int
     *
     * Gets the number of players left.
     */
    public function getPlayersLeft(): int;

    /**
     * @return int
     *
     * Gets the team size.
     */
    public function getTeamSize(): int;

    /**
     * @param $player
     * @return bool
     *
     * Determines whether or not the player is in the team.
     */
    public function isInTeam($player): bool;

    /**
     * @param callable $callable - Function used to for every player in the team.
     * Contains a player parameter.
     *          EX: function(Player $player) {}
     *
     * Broadcasts the message to everyone in the team.
     */
    public function broadcast(callable $callable): void;


    /**
     * @param Player $player
     * @param mixed ...$extraData - The extra data used for the team.
     * @return bool
     *
     * Eliminates the player from the team.
     */
    public function eliminate(Player $player, ...$extraData): bool;

    /**
     * @param $player
     * @return bool
     *
     * Determines whether the player is eliminated.
     */
    public function isEliminated($player): bool;

    /**
     * @param $object
     * @return bool
     *
     * Determines if a team is equivalent to another.
     */
    public function equals($object): bool;

    /**
     * @param Player $player
     * @return bool - Return false if team is full.
     *
     * Adds the player to the team.
     */
    public function addPlayer(Player $player): bool;

    /**
     * @return bool
     *
     * Determines if the team is full.
     */
    public function isFull(): bool;
}