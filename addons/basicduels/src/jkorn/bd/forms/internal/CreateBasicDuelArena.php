<?php

declare(strict_types=1);

namespace jkorn\bd\forms\internal;


use pocketmine\Player;

class CreateBasicDuelArena extends BasicDuelInternalForm
{

    /**
     * @param Player $player
     * @param mixed ...$args
     *
     * Called when the display method first occurs.
     */
    protected function onDisplay(Player $player, ...$args): void
    {
        // TODO: Implement onDisplay() method.
    }

    /**
     * @param Player $player
     * @return bool
     *
     * Tests the form's permissions to see if the player can use it.
     */
    protected function testPermission(Player $player): bool
    {
        // TODO: Implement testPermission() method.
        return true;
    }

    /**
     * @return string
     *
     * Gets the localized name of the internal form.
     */
    public function getLocalizedName(): string
    {
        return self::CREATE_BASIC_DUEL_ARENA;
    }
}