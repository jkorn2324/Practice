<?php

declare(strict_types=1);

namespace jkorn\practice\games\duels\types;


use jkorn\practice\games\duels\AbstractDuel;
use jkorn\practice\games\duels\teams\DuelTeam;
use jkorn\practice\games\misc\ITeamGame;
use jkorn\practice\games\misc\TeamColor;
use jkorn\practice\kits\IKit;
use jkorn\practice\player\PracticePlayer;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\Player;

abstract class TeamDuel extends AbstractDuel implements ITeamGame
{

    /** @var int */
    private $teamSize;

    /** @var bool */
    protected $generated = false;

    /** @var DuelTeam */
    protected $team1, $team2;

    public function __construct(int $teamSize, IKit $kit, $teamClass, $playerClass)
    {
        parent::__construct($kit);

        $this->teamSize = $teamSize;

        // Generates the team colors.
        $teamColor1 = TeamColor::random();
        do
        {
            $teamColor2 = TeamColor::random();
        } while($teamColor1->equals($teamColor2));

        /** @var DuelTeam team1 */
        $this->team1 = new $teamClass($teamSize, $teamColor1, $playerClass);
        /** @var DuelTeam team2 */
        $this->team2 = new $teamClass($teamSize, $teamColor2, $playerClass);
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

        // Removes the player from the team.
        if($team1->isEliminated($teamPlayer))
        {
            if($reason === self::REASON_LEFT_SERVER)
            {
                $team1->removePlayer($teamPlayer);
            }
            return;
        }

        if($this->status === self::STATUS_STARTING)
        {
            $this->setEnded(null, self::STATUS_ENDED);

            if($reason === self::REASON_LEFT_SERVER)
            {
                $teamPlayer->setOffline();
                $this->onEnd();
                $this->die();
                return;
            }
            $teamPlayer->setEliminated();
            return;
        }

        // Checks whether the duel is still in progress.
        if($this->status !== self::STATUS_IN_PROGRESS)
        {
            return;
        }

        if($team1->eliminate($player, $reason))
        {
            $this->setEnded($team2, self::STATUS_ENDING);
            return;
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

    /**
     * @param PlayerRespawnEvent $event
     *
     * Handles when the player respawns.
     */
    protected function handlePlayerRespawn(PlayerRespawnEvent &$event): void
    {
        $player = $event->getPlayer();
        $team = $this->getTeam($player);

        if
        (
            $player instanceof PracticePlayer
            && $team !== null
        )
        {
            $teamPlayer = $team->getPlayer($player);
            if ($teamPlayer->isEliminated())
            {
                if($teamPlayer->isSpectator())
               {
                   // TODO: Set the player as a fake spectator, setting the respawn position as the center position of the duel.
                   $event->setRespawnPosition($this->getCenterPosition());
                   return;
               }

                $player->putInLobby(false);
            }
        }
    }
}