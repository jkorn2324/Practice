<?php

declare(strict_types=1);

namespace jkorn\practice\games\duels;


use jkorn\practice\games\misc\gametypes\IUpdatedGame;
use jkorn\practice\kits\IKit;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Event;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\Server;

abstract class AbstractDuel implements IUpdatedGame
{

    const STATUS_STARTING = 0;
    const STATUS_IN_PROGRESS = 1;
    const STATUS_ENDING = 2;
    const STATUS_ENDED = 3;

    /** @var int - Constant used to determine the number of
     * ticks passed before we add the player to the duel. */
    const PUT_PLAYER_TICKS = 5;

    /** @var int - The current status of the duel. */
    protected $status = self::STATUS_STARTING;

    /** @var int */
    protected $countdownSeconds = 5, $endingSeconds = 2;
    /** @var int */
    protected $durationSeconds = 0;
    /** @var int */
    protected $currentTicks = 0;

    /** @var Server */
    protected $server;

    /** @var IKit */
    protected $kit;

    /** @var array */
    protected $results = [];

    /**
     * AbstractDuel constructor.
     * @param IKit $kit
     */
    public function __construct(IKit $kit)
    {
        $this->server = Server::getInstance();
        $this->kit = $kit;
        $this->results = [
            "winner" => null,
            "loser" => null
        ];
    }

        /**
     * Puts the players in the duel.
     */
    abstract protected function putPlayersInDuel(): void;

    /**
     * Updates the game.
     */
    public function update(): bool
    {
        $checkSeconds = $this->currentTicks % 20 === 0 && $this->currentTicks !== 0;

        if($this->status === self::STATUS_STARTING)
        {
            // After 5 ticks, put players in duel.
            if($this->currentTicks === self::PUT_PLAYER_TICKS)
            {
                $this->putPlayersInDuel();
                $this->currentTicks++;
                return true;
            }

            // Calls the in starting tick & updates the countdown.
            $result = $this->inStartingTick($checkSeconds);
            if($checkSeconds)
            {
                $this->countdownSeconds--;
            }

            $this->currentTicks++;
            return $result;
        }
        elseif ($this->status === self::STATUS_IN_PROGRESS)
        {
            $this->inProgressTick($checkSeconds);

            if($checkSeconds)
            {
                $this->durationSeconds++;
            }
        }
        elseif ($this->status === self::STATUS_ENDING)
        {
            if($checkSeconds && $this->endingSeconds > 0)
            {
                $this->endingSeconds--;

                if($this->endingSeconds === 0)
                {
                    $this->status = self::STATUS_ENDED;
                }
            }
        }
        elseif ($this->status === self::STATUS_ENDED)
        {
            $this->onEnd();
            $this->die();
            return false;
        }

        $this->currentTicks++;
        return true;
    }

    /**
     * @param bool $checkSeconds
     * @return bool - Whether or not the duel should continue to tick.
     *
     * Called in update function when duel is starting, doesn't run on
     * the tick where the players are being added.
     */
    abstract protected function inStartingTick(bool $checkSeconds): bool;

    /**
     * @param bool $checkSeconds
     *
     * Called when the duel is in progress.
     */
    abstract protected function inProgressTick(bool $checkSeconds): void;

    /**
     * Called when the duel has officially ended.
     */
    abstract protected function onEnd(): void;

    /**
     * @return Position
     *
     * Gets the center position of the duel.
     */
    abstract protected function getCenterPosition(): Position;
    
    /**
     * @param Player $player
     * @return string
     *
     * Gets the countdown message of the duel.
     */
    abstract protected function getCountdownMessage(Player $player): string;

    /**
     * @return Level
     *
     * Gets the level of the duel.
     */
    abstract protected function getLevel(): Level;

    /**
     * @param Event $event - The input event, here are the list
     * of events that this calls.
     * - PlayerDeathEvent
     * - PlayerRespawnEvent
     * - EntityDamageEvent
     *   - EntityDamageByEntityEvent
     *   - EntityDamageByChildEntityEvent
     * - BlockPlaceEvent
     * - BlockBreakEvent
     *
     * Handles an event when the player is in the game.
     */
    public function handleEvent(Event &$event): void
    {
        if($event instanceof PlayerDeathEvent)
        {
            $this->handlePlayerDeath($event);
        }
        elseif ($event instanceof PlayerRespawnEvent)
        {
            $this->handlePlayerRespawn($event);
        }
    }

    /**
     * @param PlayerDeathEvent $event
     *
     * Handles when the player dies.
     */
    protected function handlePlayerDeath(PlayerDeathEvent &$event): void
    {
        $reason = self::REASON_DIED;
        $player = $event->getPlayer();

        $damageCause = $player->getLastDamageCause();
        if($damageCause !== null)
        {
            $damageCauseType = $damageCause->getCause();

            if(
                $damageCause instanceof EntityDamageByEntityEvent
                && $damageCauseType === EntityDamageEvent::CAUSE_SUICIDE
            )
            {
                $reason = self::REASON_UNFAIR_RESULT;
            }
        }

        $this->removeFromGame($player, $reason);

        // Sets the death message as none.
        $event->setDeathMessage("");

        $event->setXpDropAmount(0);
        $event->setDrops([]);
    }

    /**
     * @param PlayerRespawnEvent $event
     *
     * Handles when the player respawns.
     */
    abstract protected function handlePlayerRespawn(PlayerRespawnEvent &$event): void;

    /**
     * @return string
     *
     * Gets the duration of the duel.
     */
    public function getDuration(): string
    {
        $seconds = intval($this->durationSeconds % 60);
        $minutes = intval($this->durationSeconds / 60);

        $minutesSection = strval($minutes);
        $secondsSection = strval($seconds);

        if($seconds < 10)
        {
            $secondsSection = "0" . strval($seconds);
        }

        if($minutes < 10)
        {
            $minutesSection = "0" . strval($minutes);
        }

        return "{$minutesSection}:{$secondsSection}";
    }

    /**
     * @return IKit
     *
     * Gets the kit of the duel.
     */
    public function getKit(): IKit
    {
        return $this->kit;
    }

    /**
     * @return int
     *
     * Gets the countdown seconds of the duel.
     */
    public function getCountdownSeconds(): int
    {
        return $this->countdownSeconds;
    }

    /**
     * @return int
     *
     * Gets the status of the duel.
     */
    public function getStatus(): int
    {
        return $this->status;
    }
}