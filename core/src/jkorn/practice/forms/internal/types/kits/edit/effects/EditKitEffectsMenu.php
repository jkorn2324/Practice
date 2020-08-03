<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-07-31
 * Time: 19:41
 */

declare(strict_types=1);

namespace jkorn\practice\forms\internal\types\kits\edit\effects;


use jkorn\practice\forms\internal\InternalForm;
use jkorn\practice\forms\types\SimpleForm;
use jkorn\practice\kits\IKit;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class EditKitEffectsMenu extends InternalForm
{

    /**
     * @return string
     *
     * Gets the localized name of the practice form.
     */
    public function getLocalizedName(): string
    {
        return self::EDIT_KIT_EFFECTS_MENU;
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
                        $formDisplay = self::ADD_KIT_EFFECT;
                        break;
                    case 1:
                        $formDisplay = self::EDIT_KIT_EFFECT;
                        break;
                    case 2:
                        $formDisplay = self::REMOVE_KIT_EFFECT;
                        break;
                }

                if(isset($formDisplay))
                {
                    $form = InternalForm::getForm(self::EFFECT_KIT_SELECTOR_MENU);
                    if($form !== null)
                    {
                        $form->display($player, $extraData["kit"], $formDisplay);
                    }
                }
            }
        });

        $form->setTitle(TextFormat::BOLD . "Kit Effects Menu");

        $form->setContent("This menu allows you to edit the kit's effects.");
        $form->addButton(TextFormat::BOLD . "Add an Effect", 0, "textures/ui/confirm.png");
        $form->addButton(TextFormat::BOLD . "Edit an Effect", 0, "textures/ui/debug_glyph_color.png");
        $form->addButton(TextFormat::BOLD . "Remove an Effect", 0, "textures/ui/cancel.png");

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