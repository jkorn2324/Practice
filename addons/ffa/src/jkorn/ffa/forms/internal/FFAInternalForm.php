<?php

declare(strict_types=1);

namespace jkorn\ffa\forms\internal;


use jkorn\ffa\FFAGameManager;
use jkorn\practice\forms\internal\InternalForm;
use jkorn\practice\PracticeCore;
use pocketmine\Player;

abstract class FFAInternalForm extends InternalForm implements FFAInternalFormIDs
{

    /**
     * @return FFAGameManager|null
     *
     * Gets the game manager.
     */
    protected function getGameManager(): ?FFAGameManager
    {
        $manager = PracticeCore::getBaseGameManager()->getGameManager(FFAGameManager::GAME_TYPE);
        if($manager instanceof FFAGameManager)
        {
            return $manager;
        }
        return null;
    }
}