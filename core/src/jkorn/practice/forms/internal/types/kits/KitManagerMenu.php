<?php

declare(strict_types=1);

namespace jkorn\practice\forms\internal\types\kits;


use jkorn\practice\forms\internal\IInternalForm;
use jkorn\practice\forms\internal\InternalForms;
use jkorn\practice\forms\types\SimpleForm;
use jkorn\practice\player\PracticePlayer;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class KitManagerMenu implements IInternalForm
{

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
            if(
                $player instanceof PracticePlayer
                && $player->isInGame()
            )
            {
                // TODO: Send message.
                return;
            }

            if($data !== null)
            {
                switch((int)$data)
                {
                    case 0:
                        $form = InternalForms::getForm(self::CREATE_KIT_FORM);
                        if($form !== null) {
                            $form->display($player);
                        }
                        break;
                    case 1:
                        $form = InternalForms::getForm(self::KIT_SELECTOR);
                        if($form !== null) {
                            $form->display($player, self::EDIT_KIT_MENU);
                        }
                        break;
                    case 2:
                        $form = InternalForms::getForm(self::KIT_SELECTOR);
                        if($form !== null) {
                            $form->display($player, self::DELETE_KIT_FORM);
                        }
                        break;
                    case 3:
                        $form = InternalForms::getForm(self::KIT_SELECTOR);
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

    /**
     * @return string
     *
     * Gets the localized name of the practice form.
     */
    public function getLocalizedName(): string
    {
        return self::KIT_MANAGER_MENU;
    }
}