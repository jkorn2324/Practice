<?php

declare(strict_types=1);

namespace jkorn\practice\games;


use jkorn\practice\forms\display\properties\FormDisplayStatistic;
use jkorn\practice\games\misc\IAwaitingGameManager;
use jkorn\practice\scoreboard\display\statistics\ScoreboardStatistic;
use pocketmine\Player;
use pocketmine\Server;
use jkorn\practice\PracticeCore;
use pocketmine\utils\TextFormat;

/**
 * Class BaseGameManager
 * @package jkorn\practice\games
 *
 * The main game manager.
 */
class BaseGameManager
{

    const STATISTIC_GAMES_TYPE = "games.stat.type";
    const STATISTIC_GAMES_TYPE_PLAYERS = "games.stat.type.players";
    const STATISTIC_GAMES_PLAYERS = "games.stat.players";

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
        $this->registerFormStatistics();
        $this->registerScoreboardStatistics();
    }

    /**
     * Registers the form statistics, etc...
     */
    private function registerFormStatistics(): void
    {
        // Registers the total game players.
        FormDisplayStatistic::registerStatistic(
            new FormDisplayStatistic(self::STATISTIC_GAMES_PLAYERS,
                function(Player $player, Server $server, $data)
                {
                    $playersCount = 0;
                    $games = PracticeCore::getBaseGameManager()->getGameTypes();
                    foreach($games as $game)
                    {
                        $playersCount += $game->getPlayersPlaying();
                    }

                    return $playersCount;
                })
        );

        // Registers the total game players based on type.
        FormDisplayStatistic::registerStatistic(
            new FormDisplayStatistic(self::STATISTIC_GAMES_TYPE_PLAYERS,
                function(Player $player, Server $server, $data)
                {
                    if($data instanceof IGameManager)
                    {
                        return $data->getPlayersPlaying();
                    }
                    return 0;
                })
        );

        FormDisplayStatistic::registerStatistic(
            new FormDisplayStatistic(self::STATISTIC_GAMES_TYPE,
            function(Player $player, Server $server, $data)
            {
                if($data instanceof IGameManager)
                {
                    return $data->getTitle();
                }
                return TextFormat::RED . "Unknown";
            })
        );
    }

    /**
     * Register the scoreboard statistics.
     */
    private function registerScoreboardStatistics(): void
    {
        // Gets the number of players in games.
        ScoreboardStatistic::registerStatistic(new ScoreboardStatistic(
            self::STATISTIC_GAMES_PLAYERS,
            function(Player $player, Server $server)
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