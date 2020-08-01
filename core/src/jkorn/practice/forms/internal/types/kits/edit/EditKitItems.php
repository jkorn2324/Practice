<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-07-31
 * Time: 19:35
 */

declare(strict_types=1);

namespace jkorn\practice\forms\internal\types\kits\edit;


use jkorn\practice\forms\internal\IInternalForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class EditKitItems implements IInternalForm
{

    /**
     * @return string
     *
     * Gets the localized name of the practice form.
     */
    public function getLocalizedName(): string
    {
        return self::EDIT_KIT_ITEMS;
    }

    /**
     * @param Player $player
     * @param mixed ...$args
     *
     * Displays the form to the player.
     */
    public function display(Player $player, ...$args): void
    {
        if
        (
            $player instanceof PracticePlayer
            && $player->isInGame()
        )
        {
            return;
        }

        if
        (
            !isset($args[0])
            || ($kit = $args[0]) === null
            || !$kit instanceof IKit
        )
        {
            return;
        }

        // TODO: Edit form.
    }
}