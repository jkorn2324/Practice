<?php

declare(strict_types=1);

namespace jkorn\bd\duels;


use jkorn\bd\arenas\ArenaManager;
use jkorn\bd\arenas\PostGeneratedDuelArena;
use jkorn\bd\arenas\PreGeneratedDuelArena;
use jkorn\bd\BasicDuelsManager;
use jkorn\bd\player\BasicDuelPlayer;
use jkorn\practice\games\IGameManager;
use jkorn\practice\kits\IKit;
use jkorn\practice\PracticeCore;
use jkorn\practice\PracticeUtil;
use jkorn\practice\scoreboard\ScoreboardData;
use pocketmine\Player;
use jkorn\practice\games\duels\types\Duel1vs1;
use jkorn\practice\player\PracticePlayer;

class Basic1vs1 extends Duel1vs1 implements IBasicDuel
{

    // The maximum duration seconds.
    const MAX_DURATION_SECONDS = 60 * 30;

    /** @var PracticePlayer[] */
    private $spectators;

    /** @var int */
    private $id;

    /**
     * Basic1Vs1 constructor.
     * @param int $id - The id of the duel.
     * @param IKit $kit - The kit of the 1vs1.
     * @param $arena - The arena of the 1vs1.
     * @param Player $player1 - The first player of the 1vs1.
     * @param Player $player2 - The second player of the 1vs1.
     *
     * The generic 1vs1 constructor.
     */
    public function __construct(int $id, IKit $kit, $arena, Player $player1, Player $player2)
    {
        parent::__construct($kit, $arena, $player1, $player2, BasicDuelPlayer::class);

        $this->id = $id;
        $this->spectators = [];
    }

    /**
     * @return bool
     *
     * Updates the game, overriden to check if players are online or not.
     */
    public function update(): bool
    {
        if(!$this->player1->isOnline(true) || !$this->player2->isOnline(true))
        {
            return true;
        }

        return parent::update();
    }

    /**
     * @param bool $checkSeconds
     *
     * Called when the duel is in progress, do nothing for a generic duel.
     */
    protected function inProgressTick(bool $checkSeconds): void
    {
        if($checkSeconds && $this->durationSeconds >= self::MAX_DURATION_SECONDS)
        {
            $this->setEnded();
        }
    }

    /**
     * Called when the duel has officially ended.
     */
    protected function onEnd(): void
    {
        // Updates the first player.
        if($this->player1->isOnline())
        {
            // TODO: Sends the final message to player1.
            $player = $this->player1->getPlayer();
            if(!$this->player1->isDead())
            {
                $player->putInLobby(true);
            }
        }

        // Updates the second player.
        if($this->player2->isOnline())
        {
            // TODO: Sends the final message to player2.
            $player = $this->player2->getPlayer();
            if(!$this->player2->isDead())
            {
                $player->putInLobby(true);
            }
        }

        // Broadcasts everything to the spectators & resets them.
        $this->broadcastSpectators(function(Player $player)
        {
            if($player instanceof PracticePlayer)
            {
                // TODO: Send messages to the player.
                $player->putInLobby(true);
            }
        });

        // Resets the spectators.
        $this->spectators = [];
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
            $arenaManager = PracticeCore::getBaseArenaManager()->getArenaManager(ArenaManager::TYPE);
            if($arenaManager instanceof ArenaManager)
            {
                $arenaManager->open($this->arena);
            }
        }

        $genericDuelManager = PracticeCore::getBaseGameManager()->getGameManager(IGameManager::MANAGER_GENERIC_DUELS);
        if($genericDuelManager instanceof BasicDuelsManager)
        {
            $genericDuelManager->remove($this);
        }
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
     * @param $broadcast
     *
     * Adds the spectator to the game.
     */
    public function addSpectator(Player $player, bool $broadcast = true): void
    {
        // TODO: Implement addSpectator() method.
        if(!$player instanceof PracticePlayer)
        {
            return;
        }

        $serverID = $player->getServerID()->toString();
        $this->spectators[$serverID] = $player;
        // TODO: Set the player as spectating.
        $player->teleport($this->getCenterPosition());

        $scoreboardData = $player->getScoreboardData();
        if($scoreboardData->getScoreboard() !== ScoreboardData::SCOREBOARD_DUEL_SPECTATOR)
        {
            $scoreboardData->setScoreboard(ScoreboardData::SCOREBOARD_DUEL_SPECTATOR);
        }
    }

    /**
     * @param Player $player - The player being removed.
     * @param bool $broadcastMessage - Broadcasts the message.
     * @param bool $teleportToSpawn - Determines whether or not to teleport to spawn.
     *
     * Removes the spectator from the game.
     */
    public function removeSpectator(Player $player, bool $broadcastMessage = true, bool $teleportToSpawn = true): void
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
                $displayName = $player->getDisplayName();
                if($teleportToSpawn)
                {
                    // TODO: Put player in lobby.
                }
            }

            if($broadcastMessage)
            {
                // TODO: Broadcast the message.
            }
        }
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
     * @param callable $callable - Requires a player parameter.
     *      EX: function(Player $player) {}
     *
     * Broadcasts something to the spectators.
     */
    public function broadcastSpectators(callable $callable): void
    {
        foreach($this->spectators as $spectator)
        {
            if($spectator->isOnline())
            {
                $callable($spectator);
            }
        }
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
     * @param $game
     * @return bool
     *
     * Determines if the game is equivalent.
     */
    public function equals($game): bool
    {
        if($game instanceof Basic1vs1)
        {
            return $this->getID() === $game->getID();
        }
        return false;
    }

    /**
     * @return int
     *
     * Gets the number of players playing the duel in total.
     */
    public function getNumberOfPlayers(): int
    {
        return 2;
    }
}