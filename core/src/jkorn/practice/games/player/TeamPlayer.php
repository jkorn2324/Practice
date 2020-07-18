<?php

declare(strict_types=1);

namespace jkorn\practice\games\player;


abstract class TeamPlayer extends GamePlayer
{

    /** @var bool - Determines if the player is eliminated. */
    protected $eliminated = false;

    /**
     * @return bool
     *
     * Determines whether the player is eliminated or not.
     */
    public function isEliminated(): bool
    {
        return $this->eliminated;
    }


    /**
     * Sets the player as eliminated.
     */
    public function setEliminated(): void
    {
        $this->eliminated = true;
    }
}