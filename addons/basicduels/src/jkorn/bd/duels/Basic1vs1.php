<?php

declare(strict_types=1);

namespace jkorn\bd\duels;


use jkorn\bd\arenas\ArenaManager;
use jkorn\bd\arenas\IDuelArena;
use jkorn\bd\arenas\PostGeneratedDuelArena;
use jkorn\bd\arenas\PreGeneratedDuelArena;
use jkorn\bd\BasicDuelsManager;
use jkorn\bd\duels\types\BasicDuelGameType;
use jkorn\bd\messages\BasicDuelsMessageManager;
use jkorn\bd\messages\BasicDuelsMessages;
use jkorn\bd\player\BasicDuelPlayer;
use jkorn\bd\scoreboards\BasicDuelsScoreboardManager;
use jkorn\practice\arenas\PracticeArena;
use jkorn\practice\games\duels\DuelPlayer;
use jkorn\practice\kits\IKit;
use jkorn\practice\PracticeCore;
use jkorn\practice\PracticeUtil;
use jkorn\practice\scoreboard\ScoreboardData;
use pocketmine\level\Level;
use pocketmine\level\Position;
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

    /** @var IDuelArena|PracticeArena */
    private $arena;

    /** @var BasicDuelGameType */
    private $gameType;

    /**
     * Basic1Vs1 constructor.
     * @param int $id - The id of the duel.
     * @param IKit $kit - The kit of the 1vs1.
     * @param IDuelArena|PracticeArena $arena - The arena of the 1vs1.
     * @param Player $player1 - The first player of the 1vs1.
     * @param Player $player2 - The second player of the 1vs1.
     * @param BasicDuelGameType $gameType
     *
     * The generic 1vs1 constructor.
     */
    public function __construct(int $id, IKit $kit, $arena, Player $player1, Player $player2, BasicDuelGameType $gameType)
    {
        parent::__construct($kit, $player1, $player2, BasicDuelPlayer::class);

        $this->gameType = $gameType;
        $this->arena = $arena;
        $this->id = $id;
        $this->spectators = [];
    }

    /**
     * Puts the players in the duel.
     */
    protected function putPlayersInDuel(): void
    {
        // Used to update the players.
        $this->broadcastPlayers(function(Player $player)
        {
            if(!$player instanceof PracticePlayer)
            {
                return;
            }

            $player->setGamemode(0);
            $player->setImmobile(true);
            $player->clearInventory();

            $this->kit->sendTo($player, false);

            // Sets the scoreboard of the players.
            $scoreboard = $player->getScoreboardData();
            if($scoreboard !== null)
            {
                $scoreboard->setScoreboard(BasicDuelsScoreboardManager::TYPE_SCOREBOARD_DUEL_1VS1_PLAYER);
            }
        });

        $player1 = $this->player1->getPlayer();
        $player2 = $this->player2->getPlayer();

        $p1Pos = $this->arena->getP1StartPosition();
        $position = new Position($p1Pos->x, $p1Pos->y, $p1Pos->z, $this->getLevel());
        $player1->teleportOnChunkGenerated($position);

        $p2Pos = $this->arena->getP2StartPosition();
        $position = new Position($p2Pos->x, $p2Pos->y, $p2Pos->z, $this->getLevel());
        $player2->teleportOnChunkGenerated($position);
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
            $player = $this->player1->getPlayer();
            if(!$this->player1->isDead())
            {
                $player->putInLobby(true);
            }

            // TODO: Prefix
            $player->sendMessage($this->getResultMessage($player));
        }

        // Updates the second player.
        if($this->player2->isOnline())
        {
            $player = $this->player2->getPlayer();
            if(!$this->player2->isDead())
            {
                $player->putInLobby(true);
            }

            // TODO: Prefix
            $player->sendMessage($this->getResultMessage($player));
        }

        // Broadcasts everything to the spectators & resets them.
        $this->broadcastSpectators(function(Player $player)
        {
            if($player instanceof PracticePlayer && $player->isOnline())
            {
                // TODO: Send messages to the player.
                $player->putInLobby(true);
                $player->sendMessage($this->getResultMessage($player));
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

        $genericDuelManager = PracticeCore::getBaseGameManager()->getGameManager(BasicDuelsManager::NAME);
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

        // Sets the spectator's scoreboard.
        $scoreboardData = $player->getScoreboardData();
        if($scoreboardData !== null)
        {
            $scoreboardData->setScoreboard(BasicDuelsScoreboardManager::TYPE_SCOREBOARD_DUEL_SPECTATOR);
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


    /**
     * @return Position
     *
     * Gets the center position of the duel.
     */
    protected function getCenterPosition(): Position
    {
        $pos1 = $this->arena->getP1StartPosition();
        $pos2 = $this->arena->getP2StartPosition();

        $averageX = ($pos1->x + $pos2->x) / 2;
        $averageY = ($pos1->y + $pos2->y) / 2;
        $averageZ = ($pos1->z + $pos2->z) / 2;

        return new Position($averageX, $averageY, $averageZ, $this->getLevel());
    }

    /**
     * @return Level
     *
     * Gets the level of the duel.
     */
    protected function getLevel(): Level
    {
        return $this->arena->getLevel();
    }

    /**
     * @return BasicDuelGameType
     *
     * Gets the game type of the duel.
     */
    public function getGameType(): BasicDuelGameType
    {
        return $this->gameType;
    }

    /**
     * @param Player $player
     * @return string
     *
     * Gets the countdown message of the duel.
     */
    protected function getCountdownMessage(Player $player): string
    {
        $manager = PracticeCore::getBaseMessageManager()->getMessageManager(BasicDuelsMessageManager::NAME);

        if($manager === null)
        {
            // TODO: Autogenerate countdown.
            return "";
        }

        if($this->countdownSeconds === 5) {
            $text = $manager->getMessage(BasicDuelsMessages::COUNTDOWN_SECONDS_TITLE_FIVE);
        } elseif ($this->countdownSeconds === 0) {
            $text = $manager->getMessage(BasicDuelsMessages::COUNTDOWN_SECONDS_TITLE_BEGIN);
        } else {
            $text = $manager->getMessage(BasicDuelsMessages::COUNTDOWN_SECONDS_TITLE_FOUR_THRU_ONE);
        }

        if($text !== null)
        {
            return $text->getText($player, $this);
        }

        // TODO: Autogenerate countdown.
        return "";
    }

    /**
     * @return array
     *
     * Gets the results of the duel.
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @param Player $player - The input player.
     *
     * @return string
     *
     * Gets the result message of the 1vs1.
     */
    protected function getResultMessage(Player $player): string
    {
        $results = $this->results;

        if(isset($results["winner"], $results["loser"]))
        {
            /** @var DuelPlayer|null $winner */
            $winner = $results["winner"];
            /** @var DuelPlayer|null $loser */
            $loser = $results["loser"];

            $manager = PracticeCore::getBaseMessageManager()->getMessageManager(BasicDuelsMessageManager::NAME);

            if($manager !== null)
            {
                // Always show the draw message if winner & loser is null.
                if($winner === null || $loser === null)
                {
                    $messageObject = $manager->getMessage(BasicDuelsMessages::DUELS_1VS1_RESULT_MESSAGE_DRAW);
                    if($messageObject !== null)
                    {
                        return $messageObject->getText($player, $this);
                    }
                }

                if($this->isSpectator($player))
                {
                    $messageLocalized = BasicDuelsMessages::DUELS_1VS1_RESULT_MESSAGE_SPECTATORS;
                }
                elseif ($winner->equals($player))
                {
                    $messageLocalized = BasicDuelsMessages::DUELS_1VS1_RESULT_MESSAGE_WINNER;
                }
                elseif ($loser->equals($player))
                {
                    $messageLocalized = BasicDuelsMessages::DUELS_1VS1_RESULT_MESSAGE_LOSER;
                }

                if(isset($messageLocalized))
                {
                    $messageObject = $manager->getMessage($messageLocalized);
                    if($messageObject !== null)
                    {
                        return $messageObject->getText($player, $this);
                    }
                }
            }

            $winnerDisplay = $winner !== null ? $winner->getDisplayName() : "None";
            $loserDisplay = $loser !== null ? $loser->getDisplayName() : "None";

            return "Winner: {$winnerDisplay} - Loser: {$loserDisplay}";
        }

        return "Winner: None - Loser: None";
    }
}