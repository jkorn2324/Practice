<?php

declare(strict_types=1);

namespace jkorn\bd\queues;


use jkorn\bd\BasicDuelsUtils;
use jkorn\bd\duels\types\BasicDuelGameType;
use jkorn\practice\games\duels\AbstractQueue;
use jkorn\practice\player\PracticePlayer;

class BasicQueue extends AbstractQueue
{

    /** @var BasicDuelGameType */
    private $gameType;

    /** @var bool */
    private $peOnly = false;

    public function __construct(PracticePlayer $player, $kit, BasicDuelGameType $gameType)
    {
        parent::__construct($player, $kit);
        $this->gameType = $gameType;

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
        return $this->gameType->getNumberOfPlayers();
    }

    /**
     * @return BasicDuelGameType
     *
     * Gets the game type.
     */
    public function getGameType(): BasicDuelGameType
    {
        return $this->gameType;
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
            $numPlayers = $this->getNumPlayers();
            if($this->peOnly && $numPlayers === 2)
            {
                return $queue->peOnly === $this->peOnly
                    && $queue->kit->equals($this->kit)
                    && $this->gameType->equals($queue->gameType);
            }

            return $queue->kit->equals($this->kit)
                && $this->gameType->equals($queue->gameType);
        }

        return false;
    }
}