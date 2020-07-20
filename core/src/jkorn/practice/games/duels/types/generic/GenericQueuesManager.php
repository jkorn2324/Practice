<?php

declare(strict_types=1);

namespace jkorn\practice\games\duels\types\generic;

use jkorn\practice\games\misc\IAwaitingManager;
use jkorn\practice\kits\IKit;
use jkorn\practice\player\PracticePlayer;
use jkorn\practice\PracticeCore;
use pocketmine\Player;

class GenericQueuesManager implements IAwaitingManager
{

    /** @var GenericQueue[] */
    private $queues = [];
    /** @var GenericDuelsManager */
    private $parent;

    /**
     * GenericQueuesManager constructor.
     * @param GenericDuelsManager $parent - The parent of the queues.
     *
     * Gets the constructor for the queues manager.
     */
    public function __construct(GenericDuelsManager $parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return int
     *
     * Gets the players waiting for a game.
     */
    public function getPlayersAwaiting(): int
    {
        return count($this->queues);
    }

    /**
     * @param Player $player - The player to set as awaiting.
     * @param \stdClass $data - The data of the duel.
     * @param bool $sendMessage - Determines whether or not to send a message to a player.
     *
     * Sets the player as awaiting for a game.
     */
    public function setAwaiting(Player $player, \stdClass $data, bool $sendMessage = true): void
    {
        if (
            !$player instanceof PracticePlayer
            || !isset($data->kit, $data->numberOfPlayers)
        ) {
            return;
        }

        if (
            !is_string($data->kit)
            || !$data->kit instanceof IKit
        ) {
            return;
        }

        // Gets the kit from the data.
        if (is_string($data->kit)) {
            $kit = PracticeCore::getKitManager()->get($data->kit);
        } else {
            $kit = $data->kit;
        }

        $queue = new GenericQueue($player, $kit, $data->numberOfPlayers);
        // Determines if the queue is validated.
        if (!$queue->validate()) {
            return;
        }

        if (isset($this->queues[$player->getServerID()->toString()])) {
            unset($this->queues[$player->getServerID()->toString()]);
        }

        $matched = $this->findAwaitingMatches($queue);
        if ($matched !== null) {
            $matched = $this->removeAwaitingPlayers($matched, $queue);
            $kit = $queue->getKit();
            $this->parent->create($matched, $kit, true);
            return;
        }

        $this->queues[$player->getServerID()->toString()] = $queue;
    }

    /**
     * @param GenericQueue $input
     * @return GenericQueue[]|null
     *
     * Finds a match based on the input queue.
     */
    private function findAwaitingMatches(GenericQueue &$input)
    {
        if (count($this->queues) <= 0) {
            return null;
        }

        $matches = [];
        // Gets the size of the matches array.
        $numMatches = $input->getNumPlayers() - 1;

        foreach ($this->queues as $serverID => $queue) {
            if ($queue->isMatching($input)) {
                $matches[$serverID] = $input;

                if (count($matches) === $numMatches) {
                    return $matches;
                }
            }
        }
        return null;
    }

    /**
     * @param GenericQueue[] $players
     * @param GenericQueue $input
     * @return PracticePlayer[]
     *
     * Removes the awaiting players from the list.
     */
    private function removeAwaitingPlayers(&$players, GenericQueue &$input)
    {
        $matchedPlayers = [];

        foreach ($players as $serverID => $player) {
            if (isset($this->queues[$serverID])) {
                unset($this->queues[$serverID]);
            }
            $matchedPlayers[] = $player;
        }

        $matchedPlayers[] = $input->getPlayer();
        return $matchedPlayers;
    }

    /**
     * @param Player $player - The input player.
     * @return bool - Returns true if player is awaiting, false otherwise.
     *
     * Determines whether the player is waiting for a game.
     */
    public function isAwaiting(Player $player): bool
    {
        if (!$player instanceof PracticePlayer) {
            return false;
        }

        return isset($this->queues[$player->getServerID()->toString()]);
    }

    /**
     * @param Player $player
     * @param bool $sendMessage - Determines whether or not to send the player a message.
     *
     * Removes the player from the awaiting players list.
     */
    public function removeAwaiting(Player $player, bool $sendMessage = true): void
    {
        if(!$player instanceof PracticePlayer)
        {
            return;
        }

        if(!isset($this->queues[$id = $player->getServerID()->toString()]))
        {
            return;
        }

        $queue = $this->queues[$id];
        unset($this->queues[$id]);

        if($player->isOnline() && $sendMessage)
        {
            // TODO: Send message.
        }
    }
}