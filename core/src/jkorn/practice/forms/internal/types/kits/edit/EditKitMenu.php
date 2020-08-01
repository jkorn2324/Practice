<?php

declare(strict_types=1);

namespace jkorn\practice\forms\internal\types\kits\edit;


use jkorn\practice\forms\internal\IInternalForm;
use jkorn\practice\forms\internal\InternalForms;
use jkorn\practice\forms\types\SimpleForm;
use jkorn\practice\kits\IKit;
use jkorn\practice\player\PracticePlayer;
use pocketmine\Player;
use pocketmine\utils\TextFormat;


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

        if
        (
            !isset($args[0])
            || ($kit = $args[0]) === null
            || !$kit instanceof IKit
        )
        {
            return;
        }

        $form = new SimpleForm(function(Player $player, $data, $extraData)
        {

            if($data !== null)
            {
                switch((int)$data)
                {
                    case 0:
                        $form = InternalForms::getForm(self::EDIT_KIT_ITEMS);
                        break;
                    case 1:
                        $form = InternalForms::getForm(self::EDIT_KIT_KNOCKBACK);
                        break;
                    case 2:
                        $form = InternalForms::getForm(self::EDIT_KIT_EFFECTS);
                        break;
                    case 3:
                        break;
                }

                if(isset($form) && $form instanceof IInternalForm)
                {
                    /** @var IKit $kit */
                    $kit = $extraData["kit"];
                    $form->display($player, $kit);
                }
            }
        });

        $form->setTitle(TextFormat::BOLD . "Edit Kit");
        $form->setContent("Choose whether to edit the kit's items, knockback, or effects.");

        // TODO: Get Texture
        $form->addButton(TextFormat::BOLD . "Edit Items", 0, "textures/ui/inventory_icon.png");
        $form->addButton(TextFormat::BOLD . "Edit Knockback", 0, "textures/ui/strength_effect.png");
        $form->addButton(TextFormat::BOLD . "Edit Effects", 0, "textures/ui/absorption_effect.png");

        $form->addExtraData("kit", $kit);

        $player->sendForm($form);
    }
}