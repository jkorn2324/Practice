<?php

declare(strict_types=1);

namespace jkorn\practice\games\duels;


use jkorn\practice\kits\IKit;
use jkorn\practice\player\PracticePlayer;

/**
 * Class AbstractQueue
 * @package jkorn\practice\games\duels
 *
 * The base class for duel queues.
 */
abstract class AbstractQueue
{

    /** @var IKit|null */
    protected $kit;

    /** @var PracticePlayer */
    protected $player;

    public function __construct(PracticePlayer $player, $kit)
    {
        $this->kit = $kit instanceof IKit ? $kit : null;
        $this->player = $player;
    }

    /**
     * @return bool - Return true if the queue is a valid
     *      queue, false otherwise.
     *
     * Determines whether the queue information is
     * valid, helps determines whether or not we should
     * remove the player.
     */
    abstract public function validate(): bool;

    /**
     * @param AbstractQueue $queue - Address to the queue, saves memory.
     * @return bool
     *
     * Determines whether or not the input queue is a match.
     */
    abstract public function isMatching(AbstractQueue &$queue): bool;

    /**
     * @return IKit|null
     *
     * Gets the kit of the queue.
     */
    public function getKit(): ?IKit
    {
        return $this->kit;
    }

    /**
     * @return PracticePlayer
     *
     * Gets the player from the queue.
     */
    public function getPlayer(): PracticePlayer
    {
        return $this->player;
    }
}