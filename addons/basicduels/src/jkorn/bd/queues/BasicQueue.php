<?php

declare(strict_types=1);

namespace jkorn\bd\queues;


use jkorn\bd\BasicDuelsUtils;
use jkorn\practice\games\duels\AbstractQueue;
use jkorn\practice\player\PracticePlayer;

class BasicQueue extends AbstractQueue
{

    // The number of players constants.
    const NUM_PLAYERS_1VS1 = 2;
    const NUM_PLAYERS_2VS2 = 4;
    const NUM_PLAYERS_3VS3 = 6;

    /** @var int */
    private $numberOfPlayers;

    /** @var bool */
    private $peOnly = false;

    public function __construct(PracticePlayer $player, $kit, int $numberOfPlayers)
    {
        parent::__construct($player, $kit);
        $this->numberOfPlayers = $numberOfPlayers;

        $isPe = $player->getClientInfo()->isPE();
        $property = $player->getSettingsInfo()->getProperty(BasicDuelsUtils::SETTING_PE_ONLY);
        if($property !== null && $isPe)
        {
            $this->peOnly = (bool)$property->getValue();
        }
    }

    /**
     * @return bool - Return true if the queue is a valid
     *      queue, false otherwise.
     *
     * Determines whether the queue information is
     * valid, helps determines whether or not we should
     * remove the player.
     */
    public function validate(): bool
    {
        return $this->kit !== null;
    }

    /**
     * @return int
     *
     * Gets the number of players in the queue.
     */
    public function getNumPlayers(): int
    {
        return $this->numberOfPlayers;
    }

    /**
     * @param AbstractQueue $queue - Address to the queue, saves memory.
     * @return bool
     *
     * Determines whether or not the input queue is a match.
     */
    public function isMatching(AbstractQueue &$queue): bool
    {
        // Checks if the players are equivalent.
        if($queue->player->equalsPlayer($this->player))
        {
            return false;
        }

        if(
            $queue instanceof BasicQueue
            && $queue->kit !== null
            && $this->kit !== null
        )
        {
            // Only checks for pe only queues only for a generic 1vs1.
            if($this->peOnly && $this->numberOfPlayers === 1)
            {
                return $queue->peOnly === $this->peOnly
                    && $queue->kit->equals($this->kit)
                    && $this->numberOfPlayers === $queue->numberOfPlayers;
            }

            return $queue->kit->equals($this->kit)
                && $this->numberOfPlayers === $queue->numberOfPlayers;
        }

        return false;
    }
}