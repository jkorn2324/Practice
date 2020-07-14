<?php

declare(strict_types=1);

namespace practice\games;


use practice\misc\AbstractManager;
use practice\PracticeCore;

/**
 * Class BaseGameManager
 * @package practice\games
 *
 * The main game manager.
 */
class BaseGameManager extends AbstractManager
{

    /** @var IGameManager[] */
    private $gameTypes = [];
    /** @var int */
    private $currentTicks = 0;

    public function __construct(PracticeCore $core)
    {
        parent::__construct($core, false);
    }

    /**
     * Loads the data needed for the manager, unused here.
     *
     * @param bool $async
     */
    protected function load(bool $async = false): void
    {
        // TODO: Register default games.
    }

    /**
     * @param IGameManager $manager
     * @param bool $override
     *
     * Registers the game manager to the games list.
     */
    public function registerGameManager(IGameManager $manager, bool $override = false): void
    {
        $gameType = $manager->getType();
        if(isset($this->gameTypes[$gameType]) && !$override)
        {
            return;
        }
        $this->gameTypes[$gameType] = $manager;
    }

    /**
     * Updates the base game manager.
     */
    public function update(): void
    {
        foreach($this->gameTypes as $game)
        {
            $game->update($this->currentTicks);
        }

        $this->currentTicks++;
    }

    /**
     * @return IGameManager[]
     *
     * Gets the game types, etc...
     */
    public function getGameTypes()
    {
        return $this->gameTypes;
    }

    /**
     * Saves the data from the manager, unused here.
     *
     * @param bool $async
     */
    public function save(bool $async = false): void {}
}