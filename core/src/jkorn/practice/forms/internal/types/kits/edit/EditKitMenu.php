<?php

declare(strict_types=1);

namespace jkorn\practice\forms\internal\types\kits\edit;


use jkorn\practice\forms\internal\InternalForm;
use jkorn\practice\forms\types\SimpleForm;
use jkorn\practice\kits\IKit;
use pocketmine\Player;
use pocketmine\utils\TextFormat;


class EditKitMenu extends InternalForm
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
     * Called when the display method first occurs.
     */
    protected function onDisplay(Player $player, ...$args): void
    {
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
                        $form = InternalForm::getForm(self::EDIT_KIT_ITEMS);
                        break;
                    case 1:
                        $form = InternalForm::getForm(self::EDIT_KIT_KNOCKBACK);
                        break;
                    case 2:
                        $form = InternalForm::getForm(self::EDIT_KIT_EFFECTS_MENU);
                        break;
                    case 3:
                        $form = InternalForm::getForm(self::EDIT_KIT_ICON);
                        break;
                }

                if(isset($form) && $form instanceof InternalForm)
                {
                    /** @var IKit $kit */
                    $kit = $extraData["kit"];
                    $form->display($player, $kit);
                }
            }
        });

        $form->setTitle(TextFormat::BOLD . "Edit Kit");
        $form->setContent("Choose whether to edit the kit's items, knockback, or effects.");

        $form->addButton(TextFormat::BOLD . "Edit Items", 0, "textures/ui/inventory_icon.png");
        $form->addButton(TextFormat::BOLD . "Edit Knockback", 0, "textures/ui/strength_effect.png");
        $form->addButton(TextFormat::BOLD . "Edit Effects", 0, "textures/ui/absorption_effect.png");
        $form->addButton(TextFormat::BOLD . "Edit Icon", 0, "textures/ui/color_picker.png");

        $form->addExtraData("kit", $kit);

        $player->sendForm($form);
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
}