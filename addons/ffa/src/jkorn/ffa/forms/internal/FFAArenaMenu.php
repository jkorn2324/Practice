<?php

declare(strict_types=1);

namespace jkorn\ffa\forms\internal;


use jkorn\practice\forms\internal\InternalForm;
use jkorn\practice\forms\types\properties\ButtonTexture;
use jkorn\practice\forms\types\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class FFAArenaMenu extends FFAInternalForm
{

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
                        $form = InternalForm::getForm(self::FFA_ARENA_CREATE);
                        break;
                    case 1:
                        $form = InternalForm::getForm(self::FFA_ARENA_SELECTOR);
                        $formInput = self::FFA_ARENA_EDIT;
                        break;
                    case 2:
                        $form = InternalForm::getForm(self::FFA_ARENA_SELECTOR);
                        $formInput = self::FFA_ARENA_DELETE;
                        break;
                }

                if(isset($form) && $form instanceof InternalForm)
                {
                    if(isset($formInput))
                    {
                        $form->display($player, $formInput);
                    }
                    else
                    {
                        $form->display($player);
                    }
                }
            }
        });

        $form->setTitle(TextFormat::BOLD . "FFA Arena Menu");
        $form->setContent("Select whether you want to edit, create, or delete an ffa arena.");

        $form->addButton(TextFormat::BOLD . "Create Arena",
            new ButtonTexture(ButtonTexture::TYPE_PATH, "textures/ui/confirm.png"));
        $form->addButton(TextFormat::BOLD . "Edit Arena",
            new ButtonTexture(ButtonTexture::TYPE_PATH, "textures/ui/debug_glyph_color.png"));
        $form->addButton(TextFormat::BOLD . "Delete Arena",
            new ButtonTexture(ButtonTexture::TYPE_PATH, "textures/ui/realms_red_x.png"));

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

    /**
     * @return string
     *
     * Gets the localized name of the internal form.
     */
    public function getLocalizedName(): string
    {
        return self::FFA_ARENA_MENU;
    }
}