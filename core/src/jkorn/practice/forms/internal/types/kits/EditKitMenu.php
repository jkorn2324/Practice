<?php

declare(strict_types=1);

namespace jkorn\practice\forms\internal\types\kits;


use jkorn\practice\forms\internal\IInternalForm;
use jkorn\practice\forms\types\SimpleForm;
use jkorn\practice\player\PracticePlayer;
use pocketmine\Player;

class EditKitMenu implements IInternalForm
{

    /**
     * @return string
     *
     * Gets the localized name of the practice form.
     */
    public function getLocalizedName(): string
    {
        return self::EDIT_KIT_MENU;
    }

    /**
     * @param Player $player
     * @param mixed ...$args
     *
     * Displays the form to the player.
     */
    public function display(Player $player, ...$args): void
    {
        if(
            $player instanceof PracticePlayer
            && $player->isInGame()
        )
        {
            return;
        }

        $form = new SimpleForm(function(Player $player, $data, $extraData)
        {

        });

        $player->sendForm($form);
    }
}