<?php

declare(strict_types=1);

namespace jkorn\practice\forms\internal\types\kits;


use jkorn\practice\forms\internal\InternalForm;
use jkorn\practice\forms\types\SimpleForm;
use jkorn\practice\kits\SavedKit;
use jkorn\practice\PracticeCore;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class DeleteKitForm extends InternalForm
{

    /**
     * @return string
     *
     * Gets the localized name of the practice form.
     */
    public function getLocalizedName(): string
    {
        return self::DELETE_KIT_FORM;
    }

    /**
     * @param Player $player
     * @param mixed ...$args
     *
     * Called when the display method first occurs.
     */
    protected function onDisplay(Player $player, ...$args): void
    {
        $kit = $args[0];
        if(!$kit instanceof SavedKit)
        {
            return;
        }

        $form = new SimpleForm(function(Player $player, $data, $extraData)
        {

            if(
                $data !== null
                && (int)$data === 0
            )
            {
                /** @var SavedKit $kit */
                $kit = $extraData["kit"];
                PracticeCore::getKitManager()->delete($kit);

                // TODO: Send message to player.
            }
        });


        $form->setTitle(TextFormat::BOLD . "Delete Kit");

        $content = [
            "Are you sure you want to delete the kit from the server?",
            "Select " . TextFormat::BOLD . "yes" . TextFormat::RESET . " to delete, or " . TextFormat::BOLD . "no" . TextFormat::RESET . " to cancel."
        ];

        $form->setContent(implode($content, "\n"));

        $form->addButton(TextFormat::BOLD . "Yes", 0, "textures/ui/confirm.png");
        $form->addButton(TextFormat::BOLD . "No", 0, "textures/ui/cancel.png");

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