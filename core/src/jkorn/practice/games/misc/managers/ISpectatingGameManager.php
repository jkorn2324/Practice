<?php

declare(strict_types=1);

namespace jkorn\practice\games\misc\managers;


use jkorn\practice\games\misc\gametypes\ISpectatorGame;
use pocketmine\Player;

interface ISpectatingGameManager extends IGameManager
{

    /**
     * @param Player $player
     * @return ISpectatorGame|null
     *
     * Gets the game from the spectator.
     */
    public function getFromSpectator(Player $player): ?ISpectatorGame;
}