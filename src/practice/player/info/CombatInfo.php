<?php

declare(strict_types=1);

namespace practice\player\info;

use pocketmine\Player;

class CombatInfo
{

    const MAX_COMBAT_SECONDS = 10;

    /** @var Player */
    private $player;

    /** @var int - The number of seconds in Combat. */
    private $combatSeconds;

    public function __construct(Player $player)
    {
        $this->combatSeconds = 0;
        $this->player = $player;
    }

    /**
     * @return int
     *
     * Gets the number of seconds in combat.
     */
    public function getCombatSeconds(): int
    {
        return $this->combatSeconds;
    }

    /**
     * @return bool
     *
     * Determines whether the player is in combat.
     */
    public function isInCombat(): bool
    {
        return $this->combatSeconds > 0;
    }

    /**
     * @param bool $combat
     * @param bool - Determines whether or not to send the message.
     *
     * Sets the player in combat or not.
     */
    public function setInCombat(bool $combat, bool $sendMessage = true): void
    {
        $currentCombat = $this->isInCombat();

        if ($combat) {
            $this->combatSeconds = self::MAX_COMBAT_SECONDS;
        } else {
            $this->combatSeconds = 0;
        }

        if ($currentCombat !== $combat && $sendMessage) {
            // TODO: Send combat message.
        }
    }

    /**
     * Updates the combat information.
     */
    public function update(): void
    {
        if ($this->isInCombat()) {
            $this->combatSeconds--;
            if ($this->combatSeconds <= 0) {
                $this->combatSeconds = 0;
                // TODO: Send combat message.
            }
        }
    }


}