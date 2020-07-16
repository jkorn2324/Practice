<?php

declare(strict_types=1);

namespace jkorn\practice\games\duels\types\generic;


use pocketmine\Server;
use jkorn\practice\games\IGameManager;
use jkorn\practice\PracticeCore;

class GenericDuelsManager implements IGameManager
{

    /** @var Server */
    private $server;
    /** @var PracticeCore */
    private $core;

    /** @var IGenericDuel[] */
    private $duels;

    public function __construct(PracticeCore $core)
    {
        $this->core = $core;
        $this->server = $core->getServer();

        $this->duels = [];
    }

    /**
     * Updates the games in the game manager.
     * @param int $currentTick
     */
    public function update(int $currentTick): void
    {
        foreach($this->duels as $duel)
        {
            $duel->update();
        }
    }

    /**
     * @return string
     *
     * Gets the type of game manager.
     */
    public function getType(): string
    {
        return self::MANAGER_GENERIC_DUELS;
    }

    /**
     * @return string
     *
     * Gets the title of the type of game.
     */
    public function getTitle(): string
    {
        return "Generic Duels";
    }
}