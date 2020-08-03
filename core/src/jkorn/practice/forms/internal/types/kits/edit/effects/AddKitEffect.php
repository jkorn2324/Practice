<?php

declare(strict_types=1);

namespace jkorn\practice\forms\internal\types\kits\edit\effects;


use jkorn\practice\forms\types\CustomForm;
use jkorn\practice\misc\EffectInformation;
use pocketmine\entity\EffectInstance;
use jkorn\practice\forms\internal\InternalForm;
use jkorn\practice\kits\IKit;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class AddKitEffect extends InternalForm
{

    /**
     * @return string
     *
     * Gets the localized name of the practice form.
     */
    public function getLocalizedName(): string
    {
        return self::ADD_KIT_EFFECT;
    }

    /**
     * @param Player $player
     * @param mixed ...$args
     *
     * Called when the display method first occurs.
     */
    protected function onDisplay(Player $player, ...$args): void
    {
        // TODO: Implement onDisplay() method.
        if
        (
            !isset($args[0])
            || ($kit = $args[0]) === null
            || !$kit instanceof IKit
        )
        {
            return;
        }

        if
        (
            !isset($args[1])
            || ($effect = $args[1]) === null
            || !$effect instanceof EffectInstance
        )
        {
            return;
        }

        $form = new CustomForm(function(Player $player, $data, $extraData)
        {
            if($data !== null && isset($extraData["kit"], $extraData["effect"]))
            {
                /** @var IKit $kit */
                $kit = $extraData["kit"];
                /** @var EffectInstance $effect */
                $effect = $extraData["effect"];

                if($data === 0)
                {
                    $kit->getEffectsData()->addEffect($effect);
                    // TODO: Send message saying effect has been added.
                }
            }
        });

        $effectInformation = EffectInformation::getInformation($effect);

        $form->setTitle(TextFormat::BOLD . "Add Effect");

        $content = [
            "Are you sure you want to add this effect to the kit?",
            "",
            "Effect: " . $effectInformation->getName(),
            "",
            "Select " . TextFormat::BOLD . "yes" . TextFormat::RESET . " to add, or " . TextFormat::BOLD . "no" . TextFormat::RESET . " to cancel."
        ];

        $form->setContent(implode($content, "\n"));

        $form->addButton(TextFormat::BOLD . "Yes", 0, "textures/ui/confirm.png");
        $form->addButton(TextFormat::BOLD . "No", 0, "textures/ui/cancel.png");

        $form->addExtraData("kit", $kit);
        $form->addExtraData("effect", $effect);

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
        // TODO
        return true;
    }
}