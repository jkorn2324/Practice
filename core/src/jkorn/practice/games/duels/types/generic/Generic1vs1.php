<?php

declare(strict_types=1);

namespace jkorn\practice\games\duels\types\generic;


use jkorn\practice\arenas\types\duels\DuelArenaManager;
use jkorn\practice\arenas\types\duels\PostGeneratedDuelArena;
use jkorn\practice\arenas\types\duels\PreGeneratedDuelArena;
use jkorn\practice\games\IGameManager;
use jkorn\practice\PracticeCore;
use jkorn\practice\PracticeUtil;
use pocketmine\Player;
use jkorn\practice\games\duels\player\DuelPlayer;
use jkorn\practice\games\duels\types\Duel1vs1;
use jkorn\practice\kits\Kit;
use jkorn\practice\player\PracticePlayer;

class Generic1vs1 extends Duel1vs1 implements IGenericDuel
{

    /** @var PracticePlayer[] */
    private $spectators;

    /** @var int */
    private $id;

    /**
     * Generic1vs1 constructor.
     * @param int $id - The id of the duel.
     * @param Kit $kit - The kit of the 1vs1.
     * @param $arena - The arena of the 1vs1.
     * @param Player $player1 - The first player of the 1vs1.
     * @param Player $player2 - The second player of the 1vs1.
     *
     * The generic 1vs1 constructor.
     */
    public function __construct(int $id, Kit $kit, $arena, Player $player1, Player $player2)
    {
        parent::__construct($kit, $arena, $player1, $player2, DuelPlayer::class);

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
            $arenaManager = PracticeCore::getBaseArenaManager()->getArenaManager("duels");
            if($arenaManager instanceof DuelArenaManager)
            {
                $arenaManager->open($this->arena);
            }
        }

        $genericDuelManager = PracticeCore::getBaseGameManager()->getGameManager(IGameManager::MANAGER_GENERIC_DUELS);
        if($genericDuelManager instanceof GenericDuelsManager)
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
     *
     * Adds the spectator to the game.
     */
    public function addSpectator(Player $player): void
    {
        // TODO: Implement addSpectator() method.
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
        // TODO: Implement removeSpectator() method.
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
}