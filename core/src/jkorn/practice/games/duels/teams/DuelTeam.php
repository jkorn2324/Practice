<?php

declare(strict_types=1);

namespace jkorn\practice\games\duels\teams;


use jkorn\practice\player\PracticePlayer;
use pocketmine\level\Position;
use pocketmine\Player;

abstract class DuelTeam
{

    /** @var int */
    private $teamSize;
    /** @var string */
    private $playerClass;

    /** @var DuelTeamPlayer[] */
    protected $players;
    /** @var array */
    protected $eliminated;
    /** @var string */
    private $localizedName = "";

    public function __construct(int $teamSize, $playerClass)
    {
        $this->teamSize = $teamSize;
        $this->playerClass = $playerClass;

        $this->players = [];
        $this->eliminated = [];
    }

    /**
     * @param mixed ...$extraData
     *
     * Puts the player in the game.
     */
    abstract public function putPlayersInGame(...$extraData): void;

    /**
     * @param Player $player
     * @return bool - Return false if it should stop gathering players.
     *
     * Adds the player to the team.
     */
    public function addPlayer(Player $player): bool
    {
        if(!$player instanceof PracticePlayer)
        {
            return true;
        }

        if($this->isFull())
        {
            return false;
        }

        $class = $this->playerClass;
        /** @var DuelTeamPlayer $inputPlayer */
        $inputPlayer = new $class($player);
        $this->players[$inputPlayer->getServerID()->toString()] = $inputPlayer;
        $this->localizedName .= $player->getDisplayName();
        return true;
    }

    /**
     * @return bool -
     *
     * Determines if the team is full.
     */
    public function isFull(): bool
    {
        return count($this->players) >= $this->teamSize;
    }

    /**
     * @return int
     *
     * Gets the players left in the team.
     */
    public function getPlayersLeft(): int
    {
        return $this->teamSize - count($this->eliminated);
    }

    /**
     * @return string
     *
     * Gets the localized name of the team type.
     */
    public function getLocalizedName(): string
    {
        return $this->localizedName;
    }

    /**
     * @param $player - The input player.
     * @return bool - True if the player is in the team.
     *
     * Determines if the player is in the team.
     */
    public function isInTeam($player): bool
    {
        if($player instanceof PracticePlayer)
        {
            return isset($this->players[$player->getServerID()->toString()]);
        }
        elseif ($player instanceof DuelTeamPlayer)
        {
            return isset($this->players[$player->getServerID()->toString()]);
        }

        return false;
    }

    /**
     * @param callable $callable
     *
     * Broadcasts the function to all the players in the team.
     */
    public function broadcast(callable $callable): void
    {
        foreach($this->players as $player)
        {
            if($player->isOnline())
            {
                $callable($player->getPlayer());
            }
        }
    }

    /**
     * @param $player
     * @return bool
     *
     * Determines if the team is eliminated.
     */
    public function isEliminated($player): bool
    {
        if($player instanceof PracticePlayer)
        {
            return isset($this->players[$player->getServerID()->toString()]);
        }
        elseif ($player instanceof DuelTeamPlayer)
        {
            return isset($this->players[$player->getServerID()->toString()]);
        }
        return false;
    }

    /**
     * @param Player $player
     * @param mixed ...$extraData - The extraData containing the reason why the player was eliminated.
     * @return bool - Returns true if all members are eliminated.
     *
     * Eliminates the player.
     */
    abstract public function eliminate(Player $player, ...$extraData): bool;

    /**
     * @param $object
     * @return bool
     *
     * Determines if the team is equivalent to another.
     */
    abstract public function equals($object): bool;

    /**
     * @param $player
     * @return DuelTeamPlayer|null
     */
    public function getPlayer($player)
    {
        if(
            $player instanceof PracticePlayer
            && isset($this->players[$player->getServerID()->toString()])
        )
        {
            return $this->players[$player->getServerID()->toString()];
        }
        elseif (
            $player instanceof DuelTeamPlayer
            && isset($this->players[$player->getServerID()->toString()])
        )
        {
            return $this->players[$player->getServerID()->toString()];
        }
        return null;
    }
}