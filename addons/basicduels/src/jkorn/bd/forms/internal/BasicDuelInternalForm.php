<?php

declare(strict_types=1);


namespace jkorn\bd\forms\internal;


use jkorn\bd\BasicDuelsManager;
use jkorn\practice\forms\internal\InternalForm;
use jkorn\practice\PracticeCore;

abstract class BasicDuelInternalForm extends InternalForm implements BasicDuelInternalFormIDs
{

    /**
     * @return BasicDuelsManager|null
     *
     * Gets the duels game manager.
     */
    protected function getGameManager(): ?BasicDuelsManager
    {
        $manager = PracticeCore::getBaseGameManager()->getGameManager(BasicDuelsManager::NAME);
        if($manager instanceof BasicDuelsManager)
        {
            return $manager;
        }
        return null;
    }
}