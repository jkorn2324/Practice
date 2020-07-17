<?php

declare(strict_types=1);

namespace jkorn\practice\games\duels\types;


use jkorn\practice\games\duels\AbstractDuel;
use jkorn\practice\games\duels\teams\DuelTeam;
use jkorn\practice\games\misc\ITeamGame;
use jkorn\practice\kits\Kit;
use jkorn\practice\player\PracticePlayer;
use pocketmine\level\Position;
use pocketmine\Player;

abstract class TeamDuel extends AbstractDuel implements ITeamGame
{

    /** @var int */
    private $teamSize;

    /** @var bool */
    protected $generated = false;

    /** @var DuelTeam */
    protected $team1, $team2;

    public function __construct(int $teamSize, Kit $kit, $arena, $teamClass, $playerClass)
    {
        parent::__construct($kit, $arena);

        $this->teamSize = $teamSize;

        /** @var DuelTeam team1 */
        $this->team1 = new $teamClass($teamSize, $playerClass);
        /** @var DuelTeam team2 */
        $this->team2 = new $teamClass($teamSize, $playerClass);
    }

    /**
     * @param bool $checkSeconds
     * @return bool - Whether or not the duel should continue to tick.
     *
     * Called in update function when duel is starting, doesn't run on
     * the tick where the players are being added.
     */
    protected function inStartingTick(bool $checkSeconds): bool
    {
        if($checkSeconds)
        {
            $countdownMessage = $this->getCountdownMessage();
            $showDuration = $this->countdownSeconds === 0 ? 10 : 20;
            $this->broadcastPlayers(function(Player $player) use($countdownMessage, $showDuration)
            {
                $player->sendTitle($countdownMessage, "", 5, $showDuration, 5);
            });

            if($this->countdownSeconds === 0)
            {
                $this->status = self::STATUS_IN_PROGRESS;
                $this->broadcastPlayers(function(Player $player)
                {
                    $player->setImmobile(false);
                });
            }
        }
        return true;
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

        return new Position($averageX, $averageY, $averageZ, $this->level);
    }

    /**
     * @param callable $callback - The callback used, requires a player parameter.
     *      Ex: broadcast(function(Player $player) {});
     *
     * Broadcasts something to everyone in the duel based on a callback.
     */
    public function broadcastPlayers(callable $callback): void
    {
        $this->team1->broadcast($callback);
        $this->team2->broadcast($callback);
    }

    /**
     * @param $player - The player.
     * @return bool
     *
     * Determines if the player is playing.
     */
    public function isPlaying($player): bool
    {
        if($player instanceof PracticePlayer)
        {
            return $this->team1->isInTeam($player)
                || $this->team2->isInTeam($player);
        }

        return false;
    }

    /**
     * @param Player $player
     * @return DuelTeam|null
     *
     * Gets the team from the player.
     */
    public function getTeam(Player $player)
    {
        if($this->team1->isInTeam($player))
        {
            return $this->team1;
        }
        elseif ($this->team2->isInTeam($player))
        {
            return $this->team2;
        }
        return null;
    }

    /**
     * @param Player $player
     * @param int $reason
     *
     * Removes the player from the game based on the reason.
     */
    public function removeFromGame(Player $player, int $reason): void
    {
        if(!$this->isPlaying($player))
        {
            return;
        }

        // Initializes the teams.
        $team1 = $this->getTeam($player);
        $team2 = $this->team1->equals($team1) ? $this->team2 : $this->team1;
        $teamPlayer = $team1->getPlayer($player);

        if($reason === self::STATUS_STARTING)
        {
            $teamPlayer->setDead();
            $this->setEnded(null, self::STATUS_ENDED);

            if($reason === self::REASON_LEFT_SERVER)
            {
                $teamPlayer->setOffline();
                $this->onEnd();
                $this->die();
            }
            return;
        }

        if($team1->eliminate($player, $reason))
        {
            $this->setEnded($team2, self::STATUS_ENDING);
            return;
        }

        if($reason !== self::REASON_LEFT_SERVER)
        {
            $teamPlayer->setSpectator();
        }
    }

    /**
     * @param DuelTeam $winner - The winner duel team.
     * @param DuelTeam $loser - The loser duel team.
     *
     * Sets the results of the team duel.
     */
    protected function setResults(DuelTeam &$winner, DuelTeam &$loser): void
    {
        $this->results["winner"] = $winner;
        $this->results["loser"] = $loser;
    }

    /**
     * @param DuelTeam|null $winner
     * @param int $status - The ending status of the duel.
     *
     * Sets the duel as ended, provides extra data.
     */
    protected function setEnded($winner = null, int $status = self::STATUS_ENDING): void
    {
        if($winner instanceof DuelTeam)
        {
            if($winner->equals($this->team1))
            {
                $this->setResults($this->team1, $this->team2);
            }
            elseif ($winner->equals($this->team2))
            {
                $this->setResults($this->team2, $this->team1);
            }
        }

        $this->status = $status;
    }

    /**
     * @return int
     *
     * Gets the team size.
     */
    public function getTeamSize(): int
    {
        return $this->teamSize;
    }

    /**
     * @return bool
     *
     * Determines if the teams are generated.
     */
    public function isTeamsGenerated(): bool
    {
        return $this->generated;
    }
}