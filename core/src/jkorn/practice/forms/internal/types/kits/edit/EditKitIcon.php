<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-08-14
 * Time: 20:37
 */

declare(strict_types=1);

namespace jkorn\practice\forms\internal\types\kits\edit;


use jkorn\practice\forms\internal\InternalForm;
use pocketmine\Player;

class EditKitIcon extends InternalForm
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
        return self::EDIT_KIT_ICON;
    }
}