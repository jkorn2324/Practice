<?php

declare(strict_types=1);

namespace jkorn\practice\games\duels\types;


use pocketmine\level\Position;
use pocketmine\Player;
use jkorn\practice\games\duels\AbstractDuel;
use jkorn\practice\games\duels\player\DuelPlayer;
use jkorn\practice\kits\Kit;
use jkorn\practice\player\PracticePlayer;

abstract class Duel1vs1 extends AbstractDuel
{

    /** @var DuelPlayer */
    protected $player1, $player2;

    /**
     * Duel1vs1 constructor.
     * @param Kit $kit
     * @param $arena - The input arena.
     * @param Player $player1 - The first player.
     * @param Player $player2 - The second player.
     * @param $playerTypeClass - The duel player type class.
     */
    public function __construct(Kit $kit, $arena, Player $player1, Player $player2, $playerTypeClass)
    {
        parent::__construct($kit, $arena);

        /** @var DuelPlayer player1 */
        $this->player1 = new $playerTypeClass($player1);
        /** @var DuelPlayer player2 */
        $this->player2 = new $playerTypeClass($player2);
    }

    /**
     * Puts the players in the duel.
     */
    protected function putPlayersInDuel(): void
    {
        $player1 = $this->player1->getPlayer();
        $player2 = $this->player2->getPlayer();

        $player1->setGamemode(0);
        $player2->setGamemode(0);

        // TODO Disable flight

        $player1->setImmobile(true);
        $player2->setImmobile(true);

        $player1->clearInventory();
        $player2->clearInventory();

        $p1Pos = $this->arena->getP1StartPosition();
        $position = new Position($p1Pos->x, $p1Pos->y, $p1Pos->z, $this->level);
        $player1->teleport($position);

        $p2Pos = $this->arena->getP2StartPosition();
        $position = new Position($p2Pos->x, $p2Pos->y, $p2Pos->z, $this->level);
        $player2->teleport($position);

        $this->kit->sendTo($player1, false);
        $this->kit->sendTo($player2, false);
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

            $this->broadcast(function(Player $player) use($countdownMessage, $showDuration)
            {
                $player->sendTitle($countdownMessage, "", $showDuration, 5);
            });

            if($this->countdownSeconds === 0)
            {
                $this->status = self::STATUS_IN_PROGRESS;
                $this->player1->getPlayer()->setImmobile(false);
                $this->player2->getPlayer()->setImmobile(false);
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
    protected function broadcast(callable $callback): void
    {
        if($this->player1->isOnline())
        {
            $callback($this->player1->getPlayer());
        }

        if($this->player2->isOnline())
        {
            $callback($this->player2->getPlayer());
        }
    }

    /**
     * @param $player - The player.
     * @return bool
     *
     * Determines if the player is playing.
     */
    public function isPlaying($player): bool
    {
        if($player instanceof PracticePlayer || $player instanceof DuelPlayer)
        {
            return $this->player1->equals($player)
                || $this->player2->equals($player);
        }

        return false;
    }

    /**
     * @param Player $player
     * @param int $reason
     *
     * Removes the player from the game based on the reason.
     */
    public function removeFromGame(Player $player, int $reason): void
    {
        // Checks if the player is playing.
        if(!$this->isPlaying($player)) {
            return;
        }

        $loser = $this->getPlayer($player);
        // Sets the loser as dead.
        $loser->setDead();

        if($reason !== self::REASON_UNFAIR_RESULT)
        {
            $winner = $this->getOpponent($player);
            $status = self::STATUS_ENDING;

            if($reason === self::REASON_LEFT_SERVER)
            {
                $status = self::STATUS_ENDED;
                $loser->setOffline();
            }

            $this->setEnded($winner, $status);

            // Ends the game if player leaves.
            if($status === self::STATUS_ENDED)
            {
                $this->onEnd();
                $this->die();
            }
            return;
        }

        // Sets the game as ended with no winner or losers.
        $this->setEnded();
    }

    /**
     * @param $winner
     * @param int $status
     *
     * Sets the game as ended.
     */
    protected function setEnded($winner = null, int $status = self::STATUS_ENDING): void
    {
        if ($winner instanceof Player || $winner instanceof DuelPlayer)
        {
            // Checks if the winner is a player in the duel.
            if(!$this->isPlaying($winner))
            {
                return;
            }

            if($this->player1->equals($winner))
            {
                $this->setResults($this->player1, $this->player2);
            }
            elseif ($this->player2->equals($winner))
            {
                $this->setResults($this->player2, $this->player1);
            }
        }

        $this->status = $status;
    }

    /**
     * @param DuelPlayer $winner - Reference to the winner object.
     * @param DuelPlayer $loser - Reference to the loser object.
     *
     * Sets the results of the duel.
     */
    protected function setResults(DuelPlayer &$winner, DuelPlayer &$loser): void
    {
        $this->results["winner"] = $winner;
        $this->results["loser"] = $loser;
    }

    /**
     * @param $game
     * @return bool
     *
     * Determines if the game is equivalent.
     */
    public function equals($game): bool
    {
        if($game instanceof Duel1vs1)
        {
            return $game->player1->equals($this->player1)
                && $game->player2->equals($this->player2);
        }
        return false;
    }

    /**
     * @param $player
     * @return DuelPlayer|null
     *
     * Gets the opponent of the player.
     */
    public function getOpponent($player): ?DuelPlayer
    {
        if($player instanceof Player || $player instanceof DuelPlayer)
        {
            if($this->player1->equals($player))
            {
                return $this->player2;
            }
            elseif ($this->player2->equals($player))
            {
                return $this->player1;
            }
        }
        return null;
    }

    /**
     * @param $player
     * @return DuelPlayer|null
     *
     * Gets the DuelPlayer class from the corresponding player.
     */
    public function getPlayer($player): ?DuelPlayer
    {
        if($player instanceof Player)
        {
            if($this->player1->equals($player))
            {
                return $this->player1;
            }
            elseif ($this->player2->equals($player))
            {
                return $this->player2;
            }
        }
        return null;
    }
}