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
use jkorn\bd\player\team\BasicDuelTeam;
use jkorn\bd\player\team\BasicDuelTeamPlayer;
use jkorn\bd\scoreboards\BasicDuelsScoreboardManager;
use jkorn\practice\arenas\PracticeArena;
use jkorn\practice\games\duels\teams\DuelTeam;
use jkorn\practice\games\duels\teams\DuelTeamPlayer;
use jkorn\practice\games\duels\types\TeamDuel;
use jkorn\practice\kits\IKit;
use jkorn\practice\player\PracticePlayer;
use jkorn\practice\PracticeCore;
use jkorn\practice\PracticeUtil;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;

class BasicTeamDuel extends TeamDuel implements IBasicDuel
{
    // The maximum duration seconds.
    const MAX_DURATION_SECONDS = 60 * 30;

    /** @var int */
    private $id;

    /** @var PracticePlayer[] */
    private $spectators = [];

    /** @var BasicDuelGameType */
    private $gameType;

    /** @var IDuelArena|PracticeArena */
    private $arena;

    /**
     * BasicTeamDuel constructor.
     * @param int $id
     * @param IKit $kit
     * @param IDuelArena|PracticeArena $arena
     * @param BasicDuelGameType $gameType
     * @param PracticePlayer[] $players
     */
    public function __construct(int $id, IKit $kit, $arena, BasicDuelGameType $gameType, $players)
    {
        parent::__construct($gameType->getNumberOfPlayers() / 2, $kit, BasicDuelTeam::class, BasicDuelTeamPlayer::class);
        $this->id = $id;
        $this->gameType = $gameType;
        $this->arena = $arena;
        $this->generateTeams($players);
    }

    /**
     * @param PracticePlayer[] $players
     *
     * Generates the teams in the game.
     */
    public function generateTeams(array &$players): void
    {
        if($this->generated)
        {
            return;
        }

        $this->randomTeam($players);
        $this->generated = true;
    }

    /**
     * @param PracticePlayer[] $players - Address to the original players.
     *
     * Generates a random team for the players.
     */
    protected function randomTeam(array &$players): void
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
            $randomTeam = BasicDuelTeam::TEAM_2;
        }
        elseif ($this->team2->isFull())
        {
            $randomTeam = BasicDuelTeam::TEAM_1;
        }
        /** @var Player $player */
        $player = $players[$randomKey];

        if($randomTeam === BasicDuelTeam::TEAM_2)
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
        $this->team1->putPlayersInGame(BasicDuelTeam::TEAM_1, $this->arena, $this->kit, $this->getLevel());
        $this->team2->putPlayersInGame(BasicDuelTeam::TEAM_2, $this->arena, $this->kit, $this->getLevel());
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
        // Broadcasts the callable to the players.
        $this->broadcastPlayers(function(Player $player)
        {
            $team = $this->getTeam($player);

            // The Results message.
            $player->sendMessage($this->getResultsMessage($player));

            if
            (
                $player instanceof PracticePlayer
                && $team !== null
                && ($teamPlayer = $team->getPlayer($player)) !== null
                && $teamPlayer instanceof DuelTeamPlayer
            )
            {
                // Checks if player is eliminated, puts the player in lobby.
                if(!$teamPlayer->isEliminated())
                {
                    $player->putInLobby(true);
                    return;
                }

                // Checks if the player is spectating.
                if($teamPlayer->isSpectator())
                {
                    $player->putInLobby(true);
                }
            }
        });

        // Broadcasts everything to the spectators & resets them.
        $this->broadcastSpectators(function(Player $player)
        {
            $player->sendMessage($this->getResultsMessage($player));

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

        $genericDuelManager = PracticeCore::getBaseGameManager()->getGameManager(BasicDuelsManager::NAME);
        if($genericDuelManager instanceof BasicDuelsManager)
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
        if($game instanceof BasicTeamDuel)
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

        // Sets the spectator scoreboards.
        $scoreboardData = $player->getScoreboardData();
        if ($scoreboardData !== null)
        {
            $scoreboardData->setScoreboard(BasicDuelsScoreboardManager::TYPE_SCOREBOARD_DUEL_SPECTATOR);
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

    /**
     * @return int
     *
     * Gets the number of players playing the duel in total.
     */
    public function getNumberOfPlayers(): int
    {
        return $this->getTeamSize() * 2;
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
     * @param Player $player
     * @return string
     *
     * Gets the results message of the team duel.
     */
    protected function getResultsMessage(Player $player): string
    {
        $results = $this->results;

        if(isset($results["winner"], $results["loser"]))
        {
            /** @var DuelTeam|null $winner */
            $winner = $results["winner"];
            /** @var DuelTeam|null $loser */
            $loser = $results["loser"];

            $manager = PracticeCore::getBaseMessageManager()->getMessageManager(BasicDuelsMessageManager::NAME);

            if($manager !== null)
            {
                // Always show the draw message if winner & loser is null.
                if($winner === null || $loser === null)
                {
                    $messageObject = $manager->getMessage(BasicDuelsMessages::DUELS_TEAMS_RESULT_MESSAGE_FAILED);
                    if($messageObject !== null)
                    {
                        return $messageObject->getText($player, $this);
                    }
                }

                if($this->isSpectator($player))
                {
                    $messageLocalized = BasicDuelsMessages::DUELS_TEAMS_RESULT_MESSAGE_SPECTATORS;
                }
                elseif ($winner->isInTeam($player))
                {
                    $messageLocalized = BasicDuelsMessages::DUELS_TEAMS_RESULT_MESSAGE_WINNING_TEAM;
                }
                elseif ($loser->isInTeam($player))
                {
                    $messageLocalized = BasicDuelsMessages::DUELS_TEAMS_RESULT_MESSAGE_LOSING_TEAM;
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

            $winnerDisplay = $winner !== null ? $winner->getColor()->getColorName() : "None";
            $loserDisplay = $loser !== null ? $loser->getColor()->getColorName() : "None";

            return "Winner: {$winnerDisplay} - Loser: {$loserDisplay}";
        }

        return "Winner: None - Loser: None";
    }

    /**
     * @param Player $player - The player being eliminated.
     * @param int $reason - The reason the player is eliminated.
     *
     * Broadcasts the elimination to the group of players.
     */
    protected function broadcastElimination(Player $player, int $reason): void
    {
        $this->broadcastGlobal(function(Player $inputPlayer) use($player, $reason)
        {
            if(
                $inputPlayer instanceof PracticePlayer
                && $inputPlayer->equalsPlayer($player)
                && $reason === self::REASON_LEFT_SERVER
            )
            {
                return;
            }

            // TODO: Prefix.
            $message = $player->getDisplayName() . " has been eliminated.";
            $manager = PracticeCore::getBaseMessageManager()->getMessageManager(BasicDuelsMessageManager::NAME);
            if($manager !== null)
            {
                $messageObject = $manager->getMessage(BasicDuelsMessages::DUELS_TEAMS_MESSAGE_PLAYER_ELIMINATED);
                if($messageObject !== null)
                {
                    $message = $messageObject->getText($inputPlayer, $player);
                }
            }

            $player->sendMessage($message);
        });
    }
}