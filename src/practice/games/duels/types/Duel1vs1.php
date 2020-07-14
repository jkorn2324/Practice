<?php

declare(strict_types=1);

namespace practice\games\duels\types;


use pocketmine\level\Position;
use pocketmine\Player;
use practice\arenas\types\duels\PostGeneratedArena;
use practice\arenas\types\duels\PreGeneratedArena;
use practice\games\duels\AbstractDuel;
use practice\games\duels\player\DuelPlayer;
use practice\kits\Kit;
use practice\player\PracticePlayer;
use practice\PracticeUtil;

class Duel1vs1 extends AbstractDuel
{

    /** @var DuelPlayer */
    private $player1, $player2;

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

        // TODO: Get first position, second position & teleport.

        $this->kit->sendTo($player1, false);
        $this->kit->sendTo($player2, false);
    }

    /**
     * @param bool $checkSeconds
     * @return bool - Whether or not the duel should continue to tick.
     *
     * Called in update function when duel is starting.
     */
    protected function inStartingTick(bool $checkSeconds): bool
    {
        // TODO: Implement inStartingTick() method.
    }

    /**
     * @param bool $checkSeconds
     *
     * Called when the duel is in progress.
     */
    protected function inProgressTick(bool $checkSeconds): void
    {
        // TODO: Implement inProgressTick() method.
    }

    /**
     * Called when the duel has officially ended.
     */
    protected function onEnd(): void
    {
        // TODO: Implement onEnd() method.
    }

    /**
     * @return Position
     *
     * Gets the center position of the duel.
     */
    protected function getCenterPosition(): Position
    {
        // TODO: Implement getCenterPosition() method.
    }

    /**
     * @param string $message
     *
     * Broadcasts a message to everyone in the duel.
     */
    protected function broadcastMessage(string $message): void
    {
        // TODO: Implement broadcastMessage() method.
    }

    /**
     * Called to kill the game officially.
     */
    public function die(): void
    {
        if($this->arena instanceof PostGeneratedArena)
        {
            PracticeUtil::deleteLevel($this->arena->getLevel());
        }
        elseif ($this->arena instanceof PreGeneratedArena)
        {
            // TODO: Remove the arena from the duels manager.
        }
        // TODO: Remove duel from the list.
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
        // TODO: Implement removeFromGame() method.
    }

    /**
     * @param $game
     * @return bool
     *
     * Determines if the game is equivalent.
     */
    public function equals($game): bool
    {
        // TODO: Implement equals() method.
    }
}