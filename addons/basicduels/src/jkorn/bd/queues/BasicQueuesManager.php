<?php

declare(strict_types=1);

namespace jkorn\bd\queues;

use jkorn\bd\BasicDuelsManager;
use jkorn\bd\duels\types\BasicDuelGameType;
use jkorn\bd\scoreboards\BasicDuelsScoreboardManager;
use jkorn\practice\games\misc\IAwaitingManager;
use jkorn\practice\kits\IKit;
use jkorn\practice\player\PracticePlayer;
use jkorn\practice\PracticeCore;
use jkorn\practice\scoreboard\ScoreboardData;
use pocketmine\Player;

class BasicQueuesManager implements IAwaitingManager
{

    /** @var BasicQueue[] */
    private $queues = [];
    /** @var BasicDuelsManager */
    private $parent;

    /**
     * BasicQueuesManager constructor.
     * @param BasicDuelsManager $parent - The parent of the queues.
     *
     * Gets the constructor for the queues manager.
     */
    public function __construct(BasicDuelsManager $parent)
    {
        $this->parent = $parent;
    }

    /**
     * @param callable|null $callable - The callback function, requires a parameter.
     * @return int
     *
     * Gets the players waiting for a game.
     */
    public function getPlayersAwaiting(?callable $callable = null): int
    {
        if($callable === null)
        {
            return count($this->queues);
        }

        $players = 0;
        foreach($this->queues as $queue)
        {
            if($callable($queue))
            {
                $players++;
            }
        }
        return $players;
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
            || !isset($data->kit, $data->gameType)
        ) {
            return;
        }

        if (
            !(is_string($data->kit) || $data->kit instanceof IKit)
            || !$data->gameType instanceof BasicDuelGameType
        ) {
            return;
        }

        // Gets the kit from the data.
        if (is_string($data->kit)) {
            $kit = PracticeCore::getKitManager()->get($data->kit);
        } else {
            $kit = $data->kit;
        }

        $queue = new BasicQueue($player, $kit, $data->gameType);
        // Determines if the queue is validated.
        if (!$queue->validate()) {
            return;
        }

        if (isset($this->queues[$player->getServerID()->toString()])) {
            unset($this->queues[$player->getServerID()->toString()]);
        }

        $matched = $this->findAwaitingMatches($queue);
        if ($matched !== null) {
            $gameType = $queue->getGameType();
            $matched = $this->removeAwaitingPlayers($matched, $queue);
            $kit = $queue->getKit();
            $this->parent->create($matched, $kit, $gameType, true);
            return;
        }

        $this->queues[$player->getServerID()->toString()] = $queue;

        // Sets the scoreboard of the player.
        $scoreboardData = $player->getScoreboardData();
        if($scoreboardData !== null)
        {
            $scoreboardData->setScoreboard(BasicDuelsScoreboardManager::TYPE_SCOREBOARD_SPAWN_QUEUE);
        }
    }

    /**
     * @param BasicQueue $input
     * @return BasicQueue[]|null
     *
     * Finds a match based on the input queue.
     */
    private function findAwaitingMatches(BasicQueue &$input)
    {
        if (count($this->queues) <= 0) {
            return null;
        }

        $matches = [];
        // Gets the size of the matches array.
        $numMatches = $input->getNumPlayers() - 1;

        foreach ($this->queues as $serverID => $queue) {
            if ($queue->isMatching($input)) {
                $matches[] = $queue;
                if (count($matches) === $numMatches) {
                    return $matches;
                }
            }
        }
        return null;
    }

    /**
     * @param BasicQueue[] $players
     * @param BasicQueue $input
     * @return PracticePlayer[]
     *
     * Removes the awaiting players from the list.
     */
    private function removeAwaitingPlayers(&$players, BasicQueue &$input)
    {
        $matchedPlayers = [];

        foreach($players as $player) {

            $thePlayer = $player->getPlayer();
            $matchedPlayers[] = $thePlayer;

            if (isset($this->queues[$serverID = $thePlayer->getServerID()->toString()])) {
                unset($this->queues[$serverID]);
            }
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

        if($player->isOnline())
        {
            if($sendMessage)
            {
                // TODO: Send message.
            }

            // Sets the scoreboard.
            $scoreboardData = $player->getScoreboardData();
            if($scoreboardData !== null)
            {
                $scoreboardData->setScoreboard(ScoreboardData::SCOREBOARD_SPAWN_DEFAULT);
            }
        }
    }

    /**
     * @param PracticePlayer $player
     * @return BasicQueue|null
     *
     * Gets the player's awaiting queue.
     */
    public function getAwaiting(PracticePlayer $player): ?BasicQueue
    {
        if(isset($this->queues[$player->getServerID()->toString()]))
        {
            return $this->queues[$player->getServerID()->toString()];
        }
        return null;
    }
}