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
use jkorn\practice\forms\types\CustomForm;
use jkorn\practice\forms\types\properties\ButtonTexture;
use jkorn\practice\kits\IKit;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

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
        if
        (
            !isset($args[0])
            || ($kit = $args[0]) === null
            || !$kit instanceof IKit
        )
        {
            return;
        }

        $form = new CustomForm(function(Player $player, $data, $extraData)
        {
            if($data !== null && isset($extraData["kit"]))
            {
                /** @var IKit $kit */
                $kit = $extraData["kit"];
                $kitTexture = $kit->getFormButtonTexture();

                // Sets the dropdown result.
                $dropdownResult = (int)$data[1] - 1;
                // Sets the kit icon texture.
                $texture = (string)$data[2];

                if($kitTexture !== null)
                {
                    // Sets the image type & path.
                    $kitTexture->setImageType($dropdownResult);
                    $kitTexture->setPath($texture);
                }
                else
                {
                    $kitTexture = new ButtonTexture($dropdownResult, $texture);
                    $kit->setFormButtonTexture($kitTexture);
                }

                // TODO: Send message.
            }
        });

        $form->setTitle(TextFormat::BOLD . "Edit Kit Icon");
        $form->addLabel("Edit the form display icon for the kit.");

        $kitTexture = $kit->getFormButtonTexture();

        if($kitTexture === null)
        {
            $form->addDropdown("Image Type:", ["None", "Texture Path", "Image URL"], 0);
            $form->addInput("Image Path/URL:", "");
        }
        else
        {
            $imageType = $kitTexture->validate() ? -1 : $kitTexture->getImageType();

            $form->addDropdown("Image Type:", ["None", "Texture Path", "Image URL"], $imageType + 1);
            $form->addInput("Image Path/URL:", $kitTexture->getPath());
        }

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