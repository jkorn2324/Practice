<?php

declare(strict_types=1);

namespace jkorn\practice\games;


use pocketmine\Server;
use jkorn\practice\games\duels\types\generic\GenericDuelsManager;
use jkorn\practice\PracticeCore;

/**
 * Class BaseGameManager
 * @package jkorn\practice\games
 *
 * The main game manager.
 */
class BaseGameManager
{

    /** @var IGameManager[] */
    private $gameTypes = [];
    /** @var int */
    private $currentTicks = 0;

    /** @var Server */
    private $server;
    /** @var PracticeCore */
    private $core;

    public function __construct(PracticeCore $core)
    {
        $this->core = $core;
        $this->server = $core->getServer();

        $this->initDefaultGames();
    }

    /**
     * Initializes the default games.
     */
    protected function initDefaultGames(): void
    {
        $this->registerGameManager(new GenericDuelsManager($this->core));
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

        if(isset($this->gameTypes[$gameType]))
        {
            if(!$override)
            {
                return;
            }

            $oldGameManager = $this->gameTypes[$gameType];
            $oldGameManager->onUnregistered();
        }

        $this->gameTypes[$gameType] = $manager;
        $manager->onRegistered();
    }

    /**
     * @param string $type
     * @return IGameManager|null
     *
     * Gets the game manager based on its type.
     */
    public function getGameManager(string $type): ?IGameManager
    {
        if(isset($this->gameTypes[$type]))
        {
            return $this->gameTypes[$type];
        }

        return null;
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