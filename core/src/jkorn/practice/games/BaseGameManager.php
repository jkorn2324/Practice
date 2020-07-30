<?php

declare(strict_types=1);

namespace jkorn\practice\games;


use jkorn\practice\display\DisplayStatistic;
use jkorn\practice\display\DisplayStatisticNames;
use jkorn\practice\games\misc\gametypes\IGame;
use jkorn\practice\games\misc\managers\IAwaitingGameManager;
use jkorn\practice\games\misc\managers\ISpectatingGameManager;
use jkorn\practice\games\misc\managers\IGameManager;

use jkorn\practice\games\misc\gametypes\ISpectatorGame;

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
                $games = PracticeCore::getBaseGameManager()->getGameTypes();
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
                    return $data->getTitle();
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
     * @param Player $player
     * @return IGame|null
     *
     * Gets the game from the player.
     */
    public function getGame(Player $player): ?IGame
    {
        foreach($this->gameTypes as $gameType)
        {
            $game = $gameType->getFromPlayer($player);
            if($game !== null)
            {
                return $game;
            }
        }

        return null;
    }

    /**
     * @param Player $player - The input player.
     * @return IAwaitingGameManager|null - Returns the game manager if player is
     *                   awaiting in a game.
     *
     * Gets the player's current awaiting game type.
     */
    public function getAwaitingGameType(Player $player): ?IAwaitingGameManager
    {
        foreach($this->gameTypes as $gameType)
        {
            if(
                $gameType instanceof IAwaitingGameManager
            )
            {
                $awaitingManager = $gameType->getAwaitingManager();
                if($awaitingManager->isAwaiting($player))
                {
                    return $gameType;
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
        foreach($this->gameTypes as $gameType)
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
     * @return ISpectatorGame[]
     *
     * Gets the list of all spectating games currently running, used
     * for displaying a list of spectating games so the player
     * could join.
     */
    public function getSpectatingGames()
    {
        $games = [];

        foreach($this->gameTypes as $gameType)
        {
            if($gameType instanceof ISpectatingGameManager)
            {
                $games = array_replace($games, $gameType->getGames());
            }
        }

        return $games;
    }

    /**
     * Saves the data from the manager, unused here.
     *
     * @param bool $async
     */
    public function save(bool $async = false): void {}
}