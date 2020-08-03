<?php

declare(strict_types=1);

namespace jkorn\practice\forms\internal\types\kits\edit\effects;


use jkorn\practice\forms\internal\InternalForm;
use jkorn\practice\forms\types\SimpleForm;
use jkorn\practice\kits\IKit;
use jkorn\practice\misc\EffectInformation;
use pocketmine\entity\EffectInstance;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class RemoveKitEffect extends InternalForm
{

    /**
     * @return string
     *
     * Gets the localized name of the practice form.
     */
    public function getLocalizedName(): string
    {
        return self::REMOVE_KIT_EFFECT;
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
        // Checks for the kit.
        if
        (
            !isset($args[0])
            || ($kit = $args[0]) === null
            || !$kit instanceof IKit
        )
        {
            return;
        }

        // Checks for the effect.
        if
        (
            !isset($args[1])
            || ($effect = $args[1]) === null
            || !$effect instanceof EffectInstance
        )
        {
            return;
        }

        $form = new SimpleForm(function(Player $player, $data, $extraData)
        {
            if($data !== null)
            {
                /** @var IKit $kit */
                $kit = $extraData["kit"];
                /** @var EffectInstance $effect */
                $effect = $extraData["effect"];

                // Removes the effect.
                if($data === 0)
                {
                    $kit->getEffectsData()->removeEffect($effect);
                }
            }
        });

        $effectInformation = EffectInformation::getInformation($effect);

        $form->setTitle(TextFormat::BOLD . "Remove Effect");

        $content = [
            "Are you sure you want to remove the effect from the kit?",
            "",
            "Effect: " . $effectInformation->getName(),
            "",
            "Select " . TextFormat::BOLD . "yes" . TextFormat::RESET . " to remove, or " . TextFormat::BOLD . "no" . TextFormat::RESET . " to cancel."
        ];

        $form->setContent(implode($content, "\n"));

        $form->addButton(TextFormat::BOLD . "Yes", 0, "textures/ui/confirm.png");
        $form->addButton(TextFormat::BOLD . "No", 0, "textures/ui/cancel.png");

        $form->addExtraData("kit", $kit);
        $form->addExtraData("effect", $effect);

        $player->sendForm($form);
    }
}