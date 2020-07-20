<?php

declare(strict_types=1);

namespace jkorn\practice\games\misc;


use jkorn\practice\games\IGameManager;


/**
 * Interface IAwaitingGameManager
 * @package jkorn\practice\games\misc
 *
 * Class that handles game managers that contain players
 * awaiting a game.
 *
 * An example of this is for Duels. Players wait until they
 * are partnered with enough players to join a duel.
 */
interface IAwaitingGameManager extends IGameManager
{

    /**
     * @return IAwaitingManager
     *
     * Gets the awaiting manager.
     */
    public function getAwaitingManager(): IAwaitingManager;
}