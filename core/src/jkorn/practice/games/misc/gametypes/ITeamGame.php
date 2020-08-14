<?php

declare(strict_types=1);

namespace jkorn\practice\games\misc\gametypes;


use jkorn\practice\player\PracticePlayer;
use pocketmine\Player;

interface ITeamGame extends IGame
{

    /**
     * @param PracticePlayer[] $players
     *
     * Generates the teams in the game.
     */
    public function generateTeams(array &$players): void;

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
     * @param $player
     * @return mixed
     *
     * Gets the team from the player.
     */
    public function getTeam($player);

    /**
     * @param Player $player
     * @return mixed
     *
     * Gets the opposite team from the player.
     */
    public function getOppositeTeam(Player $player);
}