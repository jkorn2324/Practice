<?php

declare(strict_types=1);

namespace jkorn\practice\forms\internal\types\kits\edit\effects;


use jkorn\practice\forms\internal\InternalForm;
use pocketmine\Player;

class AddKitEffect extends InternalForm
{

    /**
     * @return string
     *
     * Gets the localized name of the practice form.
     */
    public function getLocalizedName(): string
    {
        return self::ADD_KIT_EFFECT;
    }

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
        // TODO
        return true;
    }
}