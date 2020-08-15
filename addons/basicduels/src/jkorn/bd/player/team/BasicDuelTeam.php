<?php

declare(strict_types=1);

namespace jkorn\bd\player\team;


use jkorn\bd\arenas\IDuelArena;
use jkorn\bd\BasicDuelsUtils;
use jkorn\bd\scoreboards\BasicDuelsScoreboardManager;
use jkorn\practice\games\duels\teams\DuelTeam;
use jkorn\practice\games\misc\gametypes\IGame;
use jkorn\practice\kits\IKit;
use jkorn\practice\player\PracticePlayer;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class BasicDuelTeam extends DuelTeam
{
    // Used for generic duels.
    const TEAM_1 = 0;
    const TEAM_2 = 1;

    /**
     * @param mixed ...$extraData, for this specific duel team, it requires
     * 2 parameters: The teamNumber, arena, kit, and level.
     * EX: putPlayersInGame($teamNumber, $arena, $kit);
     *
     * Puts the player in the game.
     */
    public function putPlayersInGame(...$extraData): void
    {
        if(count($extraData) < 2)
        {
            return;
        }

        /** @var int $teamNumber */
        $teamNumber = $extraData[0];
        /** @var IDuelArena $arena */
        $arena = $extraData[1];
        /** @var IKit $kit */
        $kit = $extraData[2];
        /** @var Level $level */
        $level = $extraData[3];

        // Determines whether or not to place team positions using x axis.
        $xAxis = $this->placeTeamsOnXAxis($arena, $teamNumber);

        // Gets the start position of the players.
        $position = $teamNumber === self::TEAM_1 ? clone $arena->getP1StartPosition() : clone $arena->getP2StartPosition();

        $startSubtracted = count($this->players) - 1; $difference = 2;

        $startPositionX = $position->x - $startSubtracted;
        $startPositionZ = $position->z;

        if(!$xAxis)
        {
            $startPositionX = $position->x;
            $startPositionZ = $position->z - $startSubtracted;
        }
        $position = new Position($startPositionX, $position->y, $startPositionZ, $level);

        foreach($this->players as $player)
        {
            if($player->isOnline())
            {
                $rawPlayer = $player->getPlayer();

                $rawPlayer->setGamemode(0);

                // TODO: DISABLE FLYING.

                $rawPlayer->setImmobile(true);
                $rawPlayer->clearInventory();

                $rawPlayer->teleportOnChunkGenerated($position);

                if($xAxis) {
                    $position->x += $difference;
                } else {
                    $position->z += $difference;
                }

                $kit->sendTo($rawPlayer, false);

                $scoreboardData = $rawPlayer->getScoreboardData();
                if($scoreboardData !== null)
                {
                    $scoreboardData->setScoreboard(BasicDuelsScoreboardManager::TYPE_SCOREBOARD_DUEL_TEAM_PLAYER);
                }
            }
        }
    }

    /**
     * @param IDuelArena $arena
     * @param int $teamNumber
     * @return bool
     *
     * Determines whether we want to place players corresponding to the x or z axis.
     */
    protected function placeTeamsOnXAxis(IDuelArena &$arena, int $teamNumber): bool
    {
        if($teamNumber === self::TEAM_1)
        {
            $position = clone $arena->getP1StartPosition();
            $opponentPosition = clone $arena->getP2StartPosition();
        }
        else
        {
            $position = clone $arena->getP2StartPosition();
            $opponentPosition = clone $arena->getP1StartPosition();
        }
        $xDifference = abs($position->x - $opponentPosition->x);
        $zDifference = abs($position->z - $opponentPosition->z);
        return $xDifference < $zDifference;
    }

    /**
     * @param Player $player
     * @param mixed ...$extraData - The extraData containing the reason why the player was eliminated.
     * @return bool - Returns true if all members are eliminated.
     *
     * Eliminates the player.
     */
    public function eliminate(Player $player, ...$extraData): bool
    {
        if(
            !$player instanceof PracticePlayer
            || !$this->isInTeam($player)
        )
        {
            return false;
        }

        $teamPlayer = $this->players[$serverID = $player->getServerID()->toString()];
        $this->eliminated[$serverID] = $teamPlayer->getDisplayName();

        /** @var int $reason */
        $reason = $extraData[0];
        $playersLeft = $this->getPlayersLeft();

        if($reason !== IGame::REASON_LEFT_SERVER)
        {
            // TODO: Handle unfair result.
            $teamPlayer->setEliminated();
            if($this->getPlayersLeft() !== 0)
            {
                $teamPlayer->setSpectator();
            }
            return $playersLeft === 0;
        }

        $teamPlayer->setOffline();
        $this->removePlayer($teamPlayer);
        return $playersLeft === 0;
    }

    /**
     * @param $object
     * @return bool
     *
     * Determines if the team is equivalent to another.
     */
    public function equals($object): bool
    {
        if($object instanceof BasicDuelTeam)
        {
            return $object->getLocalizedName() === $this->getLocalizedName();
        }

        return false;
    }

    /**
     * @param $player
     *
     * Removes the player from the players list of the team,
     * shouldn't be used to actually eliminate the player.
     * If you want to eliminate the player, call eliminate()
     */
    public function removePlayer($player): void
    {
        // Checks to make sure the player is eliminated.
        if(!$this->isEliminated($player))
        {
            return;
        }

        if
        (
            $player instanceof BasicDuelTeamPlayer
            && isset($this->players[$serverID = $player->getServerID()->toString()])
        )
        {
            unset($this->players[$serverID]);
        }
        elseif
        (
            $player instanceof PracticePlayer
            && isset($this->players[$serverID = $player->getServerID()->toString()])
        )
        {
            unset($this->players[$serverID]);
        }
    }

    /**
     * @return string
     *
     * Displays the players from the duel team.
     */
    public function displayPlayers(): string
    {
        $displayPlayers = [];
        foreach($this->players as $player)
        {
            $displayPlayers[$player->getDisplayName()] = TextFormat::GREEN . $player->getDisplayName();
        }

        foreach($this->eliminated as $serverID => $eliminatedDisplay)
        {
            $displayPlayers[$eliminatedDisplay] = TextFormat::RED . $eliminatedDisplay;
        }

        return implode(TextFormat::GRAY . ", " . TextFormat::RESET, array_values($displayPlayers)) . TextFormat::RESET;
    }

    /**
     * @return float
     *
     * Gets the spectator visibility percentage.
     */
    public function getSpectatorVisibilityPercentage(): float
    {
        if(count($this->players) <= 0)
        {
            return 1.0;
        }

        $total = 0.0; $count = 0;
        foreach($this->players as $player)
        {
            $settingsInformation = $player->getPlayer()->getSettingsInfo();
            $viewDuels = $settingsInformation->getProperty(BasicDuelsUtils::SETTING_SPECTATE_DUELS);
            $additivePercentage = 1.0;
            if($viewDuels !== null)
            {
                $additivePercentage = (bool)$viewDuels->getValue() ? 1.0 : 0.0;
            }
            $total = $additivePercentage;
            $count++;
        }

        // Sets the count of the visibility to prevent divisible by zero.
        if($count <= 0) $count = 1.0;

        return $total / floatval($count);
    }
}