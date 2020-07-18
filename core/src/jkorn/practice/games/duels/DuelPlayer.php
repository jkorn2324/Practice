<?php

declare(strict_types=1);

namespace jkorn\practice\games\duels;


use jkorn\practice\games\player\GamePlayer;

abstract class DuelPlayer extends GamePlayer
{
    /** @var bool */
    private $dead = false;

    /**
     * Sets the player as teleported to spawn, used to prevent death glitches.
     * Overriden duel player classes should not change this.
     */
    public function setDead(): void
    {
        $this->dead = true;
    }

    /**
     * @return bool
     *
     * Determines whether or not the player had already teleported to spawn,
     * used to prevent death glitches.
     */
    public function isDead(): bool
    {
        return $this->dead;
    }
}