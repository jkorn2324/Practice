<?php

declare(strict_types=1);

namespace jkorn\practice\games\duels\types\generic;


use jkorn\practice\arenas\types\duels\DuelArenaManager;
use jkorn\practice\arenas\types\duels\IDuelArena;
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
     * @param $game
     *
     * Removes the duel from the manager.
     */
    public function remove($game): void
    {
        if($game instanceof IGenericDuel && isset($this->duels[$game->getID()]))
        {
            unset($this->duels[$game->getID()]);
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

    /**
     * @return string
     *
     * Gets the texture of the game type, used for forms.
     */
    public function getTexture(): string
    {
        return "textures/ui/fire_resistance_effect.png";
    }

    /**
     * Called when the game manager is first registered.
     */
    public function onRegistered(): void
    {
        // TODO: Register the statistics
        PracticeCore::getBaseArenaManager()->registerArenaManager(
            new DuelArenaManager($this->core),
            true
        );
    }

    /**
     * Called when the game manager is unregistered.
     */
    public function onUnregistered(): void
    {
        // TODO: Unregister the statistics.
        // TODO: Unregister the Post-Duel Arena Manager
    }

    /**
     * @return IDuelArena
     *
     * Generates a random duel arena.
     */
    protected function randomArena(): IDuelArena
    {
        // TODO: Get a random arena.
        return null;
    }

    /**
     * @param $manager
     * @return bool
     *
     * Determines if one manager is equivalent to another.
     */
    public function equals($manager): bool
    {
        // TODO: Implement equals() method.
        return false;
    }


    /**
     * @return int
     *
     * Gets the number of players playing.
     */
    public function getPlayersPlaying(): int
    {
        // TODO: Implement getPlayersPlaying() method.
        return 0;
    }
}