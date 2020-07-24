<?php

declare(strict_types=1);

namespace jkorn\practice\games\duels\types;


use jkorn\practice\kits\IKit;
use pocketmine\Player;
use jkorn\practice\games\duels\AbstractDuel;
use jkorn\practice\games\duels\DuelPlayer;
use jkorn\practice\player\PracticePlayer;

abstract class Duel1vs1 extends AbstractDuel
{

    /** @var DuelPlayer */
    protected $player1, $player2;

    /**
     * Duel1vs1 constructor.
     * @param IKit $kit
     * @param Player $player1 - The first player.
     * @param Player $player2 - The second player.
     * @param $playerTypeClass - The duel player type class.
     */
    public function __construct(IKit $kit, Player $player1, Player $player2, $playerTypeClass)
    {
        parent::__construct($kit);

        /** @var DuelPlayer player1 */
        $this->player1 = new $playerTypeClass($player1);
        /** @var DuelPlayer player2 */
        $this->player2 = new $playerTypeClass($player2);
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
                $this->player1->getPlayer()->setImmobile(false);
                $this->player2->getPlayer()->setImmobile(false);
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