<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-08-04
 * Time: 15:45
 */

declare(strict_types=1);

namespace jkorn\practice\games\misc\managers;


use jkorn\practice\arenas\IArenaManager;

interface IArenaGameManager extends IGameManager
{

    /**
     * @return IArenaManager
     *
     * Gets the game's arena manager.
     */
    public function getArenaManager(): IArenaManager;
}