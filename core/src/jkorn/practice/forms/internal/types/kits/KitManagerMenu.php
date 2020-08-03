<?php

declare(strict_types=1);

namespace jkorn\practice\forms\internal\types\kits;


use jkorn\practice\forms\internal\InternalForm;
use jkorn\practice\forms\types\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class KitManagerMenu extends InternalForm
{

    /**
     * @return string
     *
     * Gets the localized name of the practice form.
     */
    public function getLocalizedName(): string
    {
        return self::KIT_MANAGER_MENU;
    }

    /**
     * @param Player $player
     * @return bool
     *
     * Tests the permission of the player to see if they can use the form.
     */
    public function testPermission(Player $player): bool
    {
        // TODO: Implement testPermission() method.
        return true;
    }

    /**
     * @param Player $player
     * @param mixed ...$args
     *
     * Called when the display method first occurs.
     */
    protected function onDisplay(Player $player, ...$args): void
    {
        $form = new SimpleForm(function(Player $player, $data, $extraData)
        {
            if($data !== null)
            {
                switch((int)$data)
                {
                    case 0:
                        $form = InternalForm::getForm(self::CREATE_KIT_FORM);
                        if($form !== null) {
                            $form->display($player);
                        }
                        break;
                    case 1:
                        $form = InternalForm::getForm(self::KIT_SELECTOR);
                        if($form !== null) {
                            $form->display($player, self::EDIT_KIT_MENU);
                        }
                        break;
                    case 2:
                        $form = InternalForm::getForm(self::KIT_SELECTOR);
                        if($form !== null) {
                            $form->display($player, self::DELETE_KIT_FORM);
                        }
                        break;
                    case 3:
                        $form = InternalForm::getForm(self::KIT_SELECTOR);
                        if($form !== null) {
                            $form->display($player, self::VIEW_KIT_FORM);
                        }
                        break;
                }
            }
        });

        $form->setTitle(TextFormat::BOLD . "Manage Kits");
        $form->setContent("Manage the kits in the server.");

        $form->addButton(TextFormat::BOLD . "Create Kit", 0, "textures/ui/confirm.png");
        $form->addButton(TextFormat::BOLD . "Edit Kit", 0, "textures/ui/debug_glyph_color.png");
        $form->addButton(TextFormat::BOLD . "Delete Kit", 0, "textures/ui/realms_red_x.png");
        $form->addButton(TextFormat::BOLD . "View Kit", 0, "textures/ui/magnifyingGlass.png");

        $player->sendForm($form);
    }
}