<?php

declare(strict_types=1);

namespace jkorn\practice\games\misc;


use jkorn\practice\games\IGame;
use pocketmine\Player;

interface ITeamGame extends IGame
{

    /**
     * @param Player ...$players
     *
     * Generates the teams in the game.
     */
    public function generateTeams(Player...$players): void;

    /**
     * @return bool
     *
     * Determines if the teams are generated.
     */
    public function isTeamsGenerated(): bool;

    /**
     * @return int
     *
     * Gets the team size.
     */
    public function getTeamSize(): int;

    /**
     * @param Player $player
     * @return mixed
     *
     * Gets the team from the player.
     */
    public function getTeam(Player $player);
}