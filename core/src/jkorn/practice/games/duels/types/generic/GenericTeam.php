<?php

declare(strict_types=1);

namespace jkorn\practice\games\duels\types\generic;


use jkorn\practice\arenas\types\duels\DuelArenaManager;
use jkorn\practice\arenas\types\duels\PostGeneratedDuelArena;
use jkorn\practice\arenas\types\duels\PreGeneratedDuelArena;
use jkorn\practice\games\duels\teams\DuelTeamPlayer;
use jkorn\practice\games\duels\types\TeamDuel;
use jkorn\practice\games\IGameManager;
use jkorn\practice\kits\Kit;
use jkorn\practice\player\PracticePlayer;
use jkorn\practice\PracticeCore;
use jkorn\practice\PracticeUtil;
use jkorn\practice\scoreboard\ScoreboardData;
use pocketmine\Player;

class GenericTeam extends TeamDuel implements IGenericDuel
{
    // The maximum duration seconds.
    const MAX_DURATION_SECONDS = 60 * 30;

    /** @var int */
    private $id;

    /** @var PracticePlayer[] */
    private $spectators = [];

    public function __construct(int $id, int $teamSize, Kit $kit, $arena)
    {
        parent::__construct($teamSize, $kit, $arena, GenericDuelTeam::class, GenericDuelTeamPlayer::class);

        $this->id = $id;
    }

    /**
     * @param Player ...$players
     *
     * Generates the teams in the game.
     */
    public function generateTeams(Player ...$players): void
    {
        $this->randomTeam($players);
        $this->generated = true;
    }

    /**
     * @param Player...$players - Address to the original players.
     *
     * Generates a random team for the players.
     */
    protected function randomTeam(Player& ...$players): void
    {
        if(count($players) <= 0)
        {
            return;
        }

        $keys = array_keys($players);
        $randomKey = $keys[mt_rand(0, count($players) - 1)];
        $randomTeam = mt_rand() % 2;
        if($this->team1->isFull())
        {
            $randomTeam = 1;
        }
        elseif ($this->team2->isFull())
        {
            $randomTeam = 0;
        }
        /** @var Player $player */
        $player = $players[$randomKey];

        if($randomTeam)
        {
            $this->team2->addPlayer($player);
        }
        else
        {
            $this->team1->addPlayer($player);
        }
        unset($players[$randomKey]);
        $this->randomTeam($players);
    }

    /**
     * Puts the players in the duel.
     */
    protected function putPlayersInDuel(): void
    {
        $this->team1->putPlayersInGame(GenericDuelTeam::TEAM_1, $this->arena, $this->kit, $this->level);
        $this->team2->putPlayersInGame(GenericDuelTeam::TEAM_2, $this->arena, $this->kit, $this->level);
    }

    /**
     * @param bool $checkSeconds
     *
     * Called when the duel is in progress.
     */
    protected function inProgressTick(bool $checkSeconds): void
    {
        if($checkSeconds)
        {
            if($this->durationSeconds >= self::MAX_DURATION_SECONDS)
            {
                $this->setEnded(null, self::STATUS_ENDED);
            }
        }
    }

    /**
     * Called when the duel has officially ended.
     */
    protected function onEnd(): void
    {
        // TODO: Implement onEnd() method.
    }

    /**
     * Called to kill the game officially.
     */
    public function die(): void
    {
        if($this->arena instanceof PostGeneratedDuelArena)
        {
            PracticeUtil::deleteLevel($this->arena->getLevel(), true);
        }
        elseif ($this->arena instanceof PreGeneratedDuelArena)
        {
            // Opens the duel arena again for future use.
            $arenaManager = PracticeCore::getBaseArenaManager()->getArenaManager("duels");
            if($arenaManager instanceof DuelArenaManager)
            {
                $arenaManager->open($this->arena);
            }
        }

        $genericDuelManager = PracticeCore::getBaseGameManager()->getGameManager(IGameManager::MANAGER_GENERIC_DUELS);
        if($genericDuelManager instanceof GenericDuelsManager)
        {
            $genericDuelManager->remove($this);
        }
    }

    /**
     * @param $game
     * @return bool
     *
     * Determines if the game is equivalent.
     */
    public function equals($game): bool
    {
        if($game instanceof GenericTeam)
        {
            return $game->getID() === $this->getID();
        }
        return false;
    }

    /**
     * @return int
     *
     * Gets the game's id.
     */
    public function getID(): int
    {
        return $this->id;
    }

    /**
     * @param callable $callback - The callback used, requires a player parameter.
     *      Ex: broadcast(function(Player $player) {});
     *
     * Broadcasts something to everyone in the game based on a callback.
     */
    public function broadcastGlobal(callable $callback): void
    {
        $this->broadcastPlayers($callback);
        $this->broadcastSpectators($callback);
    }

    /**
     * @param Player $player
     * @return bool
     *
     * Determines if the player is a spectator.
     */
    public function isSpectator(Player $player): bool
    {
        if($player instanceof PracticePlayer)
        {
            return isset($this->spectators[$player->getServerID()->toString()]);
        }

        return false;
    }

    /**
     * @param Player $player
     * @param bool $broadcast
     *
     * Adds the spectator to the spectator list.
     */
    public function addSpectator(Player $player, bool $broadcast = true): void
    {
        if (!$player instanceof PracticePlayer) {
            return;
        }

        $serverID = $player->getServerID()->toString();
        $this->spectators[$serverID] = $player;
        // TODO: Set the player as spectating.
        $player->teleport($this->getCenterPosition());

        $scoreboardData = $player->getScoreboardData();
        if ($scoreboardData->getScoreboard() !== ScoreboardData::SCOREBOARD_DUEL_SPECTATOR) {
            $scoreboardData->setScoreboard(ScoreboardData::SCOREBOARD_DUEL_SPECTATOR);
        }
    }

    /**
     * @param Player $player
     * @param bool $broadcast
     * @param bool $teleportToSpawn
     *
     * Removes the spectator from the game.
     */
    public function removeSpectator(Player $player, bool $broadcast = true, bool $teleportToSpawn = true): void
    {
        if(!$player instanceof PracticePlayer)
        {
            return;
        }

        $serverID = $player->getServerID()->toString();
        if(isset($this->spectators[$serverID]))
        {
            unset($this->spectators[$serverID]);
            if($player->isOnline())
            {
                // TODO: Unset the player as spectator.
                if($teleportToSpawn)
                {
                    // TODO: Put player in lobby.
                }
            }
        }
    }

    /**
     * @param callable $callable - Requires a player parameter.
     *      EX: function(Player $player) {}
     *
     * Broadcasts something to the spectators.
     */
    public function broadcastSpectators(callable $callable): void
    {
        foreach ($this->spectators as $spectator)
        {
            if($spectator->isOnline())
            {
                $callable($spectator);
            }
        }
    }
}