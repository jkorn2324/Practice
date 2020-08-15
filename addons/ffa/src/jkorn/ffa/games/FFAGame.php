<?php

declare(strict_types=1);

namespace jkorn\ffa\games;


use jkorn\ffa\arenas\FFAArena;
use jkorn\ffa\statistics\FFADisplayStatistics;
use jkorn\practice\forms\types\properties\ButtonTexture;
use jkorn\practice\games\misc\gametypes\IGame;
use jkorn\practice\player\PracticePlayer;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Event;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\Player;

class FFAGame implements IGame
{

    /** @var PracticePlayer[] */
    private $players = [];

    /** @var FFAArena|null */
    private $arena;

    public function __construct(FFAArena $arena)
    {
        $this->arena = $arena;
    }

    /**
     * @return bool - Returns true if game is valid.
     *
     * Determines whether or not the game can be joined..
     */
    public function validate(): bool
    {
        if($this->arena === null)
        {
            return false;
        }

        $kit = $this->arena->getKit();
        return $kit !== null;
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
            return isset($this->players[$player->getServerID()->toString()]);
        }

        return false;
    }


    /**
     * @param Player $player - The player to add to the game.
     * @param bool $message - Determines whether or not to send a message.
     *
     * Puts the player in the game.
     */
    public function putInGame(Player $player, bool $message = true): void
    {
        if(!$this->validate())
        {
            // TODO: Send message.
            return;
        }

        if(!$player instanceof PracticePlayer)
        {
            // TODO: Send message.
            return;
        }


        // Adds the player to the players list.
        $this->players[$player->getServerID()->toString()] = $player;
        // Teleports the player to the arena, must be set after we add the player to the list.
        $this->arena->teleportTo($player);

        if($message)
        {
            // TODO: Prefix
            $messageText = "You have joined the " . $this->arena->getName() . " ffa arena!";
            // TODO: Edit the messages.

            /* $displayManager = PracticeCore::getBaseMessageManager()->getMessageManager(PracticeMessageManager::NAME);
            if($displayManager !== null)
            {
                $theMessage = $displayManager->getMessage(IPracticeMessages::PLAYER_FFA_ARENA_JOIN_MESSAGE);
                if($message !== null)
                {
                    $messageText = $theMessage->getText($player, $this);
                }
            } */

            $player->sendMessage($messageText);
        }
    }

    /**
     * @param Player $player
     * @param int $reason
     *
     * Removes the player from the game based on the reason.
     */
    public function removeFromGame(Player $player, int $reason): void
    {
        if(!$player instanceof PracticePlayer)
        {
            return;
        }

        // Removes the player from the game indefinitely.
        if($this->isPlaying($player))
        {
            unset($this->players[$player->getServerID()->toString()]);
        }

        $addDeath = false;
        if($reason === self::REASON_DIED) {
            $addDeath = true;
        } elseif ($reason === self::REASON_LEFT_SERVER) {

            if($player !== null)
            {
                $combatInfo = $player->getCombatInfo();
                if($combatInfo !== null)
                {
                    $addDeath = $combatInfo->isInCombat();
                }
            }
        }

        if($addDeath)
        {
            $lastDamageCause = $player->getLastDamageCause();
            if($lastDamageCause instanceof EntityDamageByEntityEvent)
            {
                $lastDamager = $lastDamageCause->getDamager();
                if
                (
                    $lastDamager instanceof PracticePlayer
                    && ($game = $lastDamager->getCurrentGame()) !== null
                    && $game instanceof FFAGame && $game->equals($this)
                    && ($combatInfo = $lastDamager->getCombatInfo())->isInCombat()
                )
                {
                    // Updates the player's kills statistics.
                    $killerStatistics = $lastDamager->getStatsInfo();
                    $killsStat = $killerStatistics->getStatistic(FFADisplayStatistics::STATISTIC_FFA_PLAYER_KILLS);
                    if($killsStat !== null)
                    {
                        $killsStat->setValue($killsStat->getValue() + 1);
                    }

                    $combatInfo->setInCombat(false);
                }
            }

            $dPlayerStats = $player->getStatsInfo();
            $deathsStat = $dPlayerStats->getStatistic(FFADisplayStatistics::STATISTIC_FFA_PLAYER_DEATHS);

            if($deathsStat !== null)
            {
                $previousDeathCount = $deathsStat->getValue();
                $deathsStat->setValue($previousDeathCount + 1);
            }
        }
    }

    /**
     * @param callable $callback - The callback used, requires a player parameter.
     *      Ex: broadcast(function(Player $player) {});
     *
     * Broadcasts something to everyone playing in the game based on a callback.
     */
    public function broadcastPlayers(callable $callback): void
    {
        foreach($this->players as $player)
        {
            if($player->isOnline())
            {
                $callback($player);
            }
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
        if($game instanceof FFAGame)
        {
            if($game->arena !== null && $this->arena !== null)
            {
                return $game->arena->equals($this->arena);
            }
        }

        return false;
    }

    /**
     * @return int
     *
     * Gets the number of players playing.
     */
    public function getPlayersPlaying(): int
    {
        return count($this->players);
    }

    /**
     * @return ButtonTexture|null
     *
     * Gets the game's form button texture.
     */
    public function getFormButtonTexture(): ?ButtonTexture
    {
        if($this->arena !== null)
        {
            return $this->arena->getFormButtonTexture();
        }
        return null;
    }

    /**
     * @return FFAArena|null
     *
     * Gets the arena of the game.
     */
    public function getArena(): ?FFAArena
    {
        return $this->arena;
    }

    /**
     * @param Event $event - The input event, here are the list
     * of event that this calls.
     * - PlayerDeathEvent
     * - PlayerRespawnEvent => DO NOTHING HERE AS PLAYER IS REMOVED.
     * - EntityDamageEvent
     *   - EntityDamageByEntityEvent
     *   - EntityDamageByChildEntityEvent
     * - BlockPlaceEvent
     * - BlockBreakEvent
     * Handles an event when the player is in the game.
     */
    public function handleEvent(Event &$event): void
    {
        if($event instanceof PlayerDeathEvent)
        {
            $this->handleDeathEvent($event);
        }
        elseif ($event instanceof EntityDamageByEntityEvent)
        {
            $this->handleEntityDamageEvent($event);
        }
        elseif ($event instanceof BlockPlaceEvent)
        {
            $this->handleBlockPlaceEvent($event);
        }
        elseif ($event instanceof BlockBreakEvent)
        {
            $this->handleBlockBreakEvent($event);
        }
    }

    /**
     * @param PlayerDeathEvent $event
     *
     * Handles when the player dies in an FFA Arena, updates the kills & deaths statistics.
     */
    protected function handleDeathEvent(PlayerDeathEvent &$event): void
    {
        $player = $event->getPlayer();
        if($player instanceof PracticePlayer)
        {
            $lastDamageCause = $player->getLastDamageCause();
            if($lastDamageCause === null)
            {
                $this->removeFromGame($player, self::REASON_UNFAIR_RESULT);
                return;
            }

            $cause = $lastDamageCause->getCause();
            if($cause === EntityDamageEvent::CAUSE_SUICIDE || $cause === EntityDamageEvent::CAUSE_VOID)
            {
                $this->removeFromGame($player, self::REASON_UNFAIR_RESULT);
                return;
            }

            $this->removeFromGame($player, self::REASON_DIED);
        }
    }

    /**
     * @param EntityDamageEvent $event
     *
     * Handles when an entity is damaged in an FFA Arena. It is always
     * going to be when a player gets damaged.
     */
    protected function handleEntityDamageEvent(EntityDamageEvent &$event): void
    {
        $damaged = $event->getEntity();

        // Puts the players in combat.
        if($damaged instanceof PracticePlayer && $event instanceof EntityDamageByEntityEvent)
        {
            $damager = $event->getDamager();
            if
            (
                $damager instanceof PracticePlayer
                && ($game = $damager->getCurrentGame()) !== null
                && $game instanceof FFAGame && $game->equals($this)
            )
            {
                $damager->getCombatInfo()->setInCombat(true);
                $damaged->getCombatInfo()->setInCombat(true);
            }
        }
    }

    /**
     * @param BlockPlaceEvent $event
     *
     * Handles when a block is placed.
     */
    protected function handleBlockPlaceEvent(BlockPlaceEvent &$event): void
    {
        // TODO: Handle this with build uhc.
        $event->setCancelled();
    }

    /**
     * @param BlockBreakEvent $event
     *
     * Handles when a block is broken.
     */
    protected function handleBlockBreakEvent(BlockBreakEvent &$event): void
    {
        // TODO: Handle this with build uhc.
        $event->setCancelled();
    }
}