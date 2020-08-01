<?php

declare(strict_types=1);

namespace jkorn\ffa\games;


use jkorn\ffa\arenas\FFAArena;
use jkorn\practice\games\misc\gametypes\IGame;
use jkorn\practice\player\PracticePlayer;
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
        return $kit === null;
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

        // Teleports the player to the arena.
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

        // Adds the player to the players list.
        $this->players[$player->getServerID()->toString()] = $player;
    }

    /**
     * @param Player $player
     * @param int $reason
     *
     * Removes the player from the game based on the reason.
     */
    public function removeFromGame(Player $player, int $reason): void
    {
        // TODO: Determine if the player is in combat.

        if($this->isPlaying($player))
        {
            /** @var PracticePlayer $player */
            unset($this->players[$player->getServerID()->toString()]);
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
     * @return string
     *
     * Gets the game's texture.
     */
    public function getTexture(): string
    {
        if($this->arena !== null)
        {
            $kit = $this->arena->getKit();
            if($kit !== null)
            {
                return $kit->getTexture();
            }
        }

        return "";
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
     *
     * Handles an event when the player is in the game.
     */
    public function handleEvent(Event &$event): void
    {
        if($event instanceof PlayerDeathEvent)
        {
            // TODO: Update player deaths, update player kills.
        }
    }
}