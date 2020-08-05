<?php

declare(strict_types=1);

namespace jkorn\practice\games;


use jkorn\practice\arenas\PracticeArenaManager;
use jkorn\practice\display\DisplayStatistic;
use jkorn\practice\display\DisplayStatisticNames;
use jkorn\practice\games\misc\gametypes\IGame;
use jkorn\practice\games\misc\managers\awaiting\IAwaitingManager;
use jkorn\practice\games\misc\managers\IAwaitingGameManager;
use jkorn\practice\games\misc\managers\ISpectatingGameManager;
use jkorn\practice\games\misc\managers\IGameManager;

use jkorn\practice\games\misc\gametypes\ISpectatorGame;

use jkorn\practice\games\misc\managers\IUpdatedGameManager;
use pocketmine\Player;
use pocketmine\Server;
use jkorn\practice\PracticeCore;

/**
 * Class BaseGameManager
 * @package jkorn\practice\games
 *
 * The main game manager.
 */
class BaseGameManager implements DisplayStatisticNames
{
    /** @var IGameManager[] */
    private $gameManagers = [];
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

        $this->initStatistics();
    }

    /**
     * Initializes the Game Statistics.
     */
    protected function initStatistics(): void
    {
        // Gets the total number of players playing in games.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_GAMES_PLAYERS_PLAYING,
            function(Player $player, Server $server, $data)
            {
                $playersCount = 0;
                $games = PracticeCore::getBaseGameManager()->getGameManagers();
                foreach($games as $game)
                {
                    $playersCount += $game->getPlayersPlaying();
                }
                return $playersCount;
            }
        ));

        // Gets the number of players in a particular game type.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_GAMES_TYPE_PLAYERS_PLAYING,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof IGameManager)
                {
                    return $data->getPlayersPlaying();
                }
                return 0;
            }
        ));

        // Gets the game type name.
        DisplayStatistic::register(new DisplayStatistic(
            self::STATISTIC_GAMES_TYPE,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof IGameManager)
                {
                    return $data->getDisplayName();
                }
                return "Unknown";
            }
        , false));
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
        if(isset($this->gameManagers[$gameType]))
        {
            if(!$override)
            {
                return;
            }

            $oldGameManager = $this->gameManagers[$gameType];
            $oldGameManager->onUnregistered();
            $oldGameManager->onSave();
        }

        $this->gameManagers[$gameType] = $manager;
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
        if(isset($this->gameManagers[$type]))
        {
            return $this->gameManagers[$type];
        }

        return null;
    }

    /**
     * @param Player $player
     * @param callable|null $filter - Filters what game we do search for,
     *              must return null or a game and contain a game manager parameter.
     *              EX: function(IGameManager $manager) { return true; }
     *
     * @return IGame|null
     *
     * Gets the game from the player, by default gets the game the player is
     * playing in.
     */
    public function getGame(Player $player, ?callable $filter = null): ?IGame
    {
        if($filter !== null)
        {
            foreach($this->gameManagers as $gameManager)
            {
                $game = $filter($gameManager);
                if($game !== null && $game instanceof IGame)
                {
                    return $game;
                }
            }
            return null;
        }

        foreach($this->gameManagers as $gameManager)
        {
            $game = $gameManager->getFromPlayer($player);
            if($game !== null)
            {
                return $game;
            }
        }

        return null;
    }

    /**
     * @param Player $player - The input player.
     * @return IAwaitingManager|null - Returns the game manager if player is
     *                   awaiting in a game.
     *
     * Gets the player's current awaiting game type.
     */
    public function getAwaitingManager(Player $player): ?IAwaitingManager
    {
        foreach($this->gameManagers as $gameType)
        {
            if(
                $gameType instanceof IAwaitingGameManager
            )
            {
                $awaitingManager = $gameType->getAwaitingManager();
                if($awaitingManager->isAwaiting($player))
                {
                    return $awaitingManager;
                }
            }
        }

        return null;
    }

    /**
     * @param Player $player
     * @return ISpectatorGame|null
     *
     * Gets the game the player is spectating.
     */
    public function getSpectatingGame(Player $player): ?ISpectatorGame
    {
        foreach($this->gameManagers as $gameType)
        {
            if($gameType instanceof ISpectatingGameManager)
            {
                $game = $gameType->getFromSpectator($player);

                if($game !== null)
                {
                    return $game;
                }
            }
        }
        return null;
    }

    /**
     * Updates the base game manager.
     */
    public function update(): void
    {
        foreach($this->gameManagers as $game)
        {
            if($game instanceof IUpdatedGameManager)
            {
                $game->update($this->currentTicks);
            }
        }

        $this->currentTicks++;
    }

    /**
     * @return IGameManager[]
     *
     * Gets the game types, etc...
     */
    public function getGameManagers()
    {
        return $this->gameManagers;
    }

    /**
     * Gets all of the arena managers.
     *
     * @return PracticeArenaManager[]
     */
    public function getArenaManagers()
    {
        $managers = [];

        foreach($this->gameManagers as $gameManager)
        {
            $arenaManager = $gameManager->getArenaManager();

            if($arenaManager !== null)
            {
                $managers[$gameManager->getType()] = $arenaManager;
            }
        }
        return $managers;
    }

    /**
     * @return ISpectatorGame[]
     *
     * Gets the list of all spectating games currently running, used
     * for displaying a list of spectating games so the player
     * could join.
     */
    public function getSpectatingGames()
    {
        $games = [];

        foreach($this->gameManagers as $gameType)
        {
            if($gameType instanceof ISpectatingGameManager)
            {
                $games = array_replace($games, $gameType->getGames());
            }
        }

        return $games;
    }

    /**
     * Saves the data from the managers.
     *
     * @param bool $async
     */
    public function save(bool $async = false): void
    {
        foreach($this->gameManagers as $gameManager)
        {
            $gameManager->onSave();
        }
    }
}