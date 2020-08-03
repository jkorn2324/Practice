<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-08-02
 * Time: 19:38
 */

declare(strict_types=1);

namespace jkorn\practice\forms\internal\types\kits\edit\effects;


use jkorn\practice\forms\internal\InternalForm;
use jkorn\practice\forms\types\CustomForm;
use jkorn\practice\kits\IKit;
use jkorn\practice\misc\EffectInformation;
use pocketmine\Player;
use pocketmine\entity\EffectInstance;
use pocketmine\utils\TextFormat;

class EditKitEffect extends InternalForm
{

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

                $duration = $data[2];
                $amplifier = $data[3];

                if(!is_numeric($duration) || !is_numeric($amplifier))
                {
                    return;
                }

                // Duration in Seconds
                $duration = intval($duration) * 20;
                $amplifier = intval($amplifier);

                if($effect->getAmplifier() !== $amplifier)
                {
                    $effect->setAmplifier($amplifier);
                }

                if($effect->getDuration() !== $duration)
                {
                    $effect->setDuration($duration);
                }

                // Overrides the effect.
                $kit->getEffectsData()->addEffect($effect);

                // TODO: Send message saying player successfully edited the kit.
            }
        });

        $effectInformation = EffectInformation::getInformation($effect);

        $form->setTitle(TextFormat::BOLD . "Edit Effect");
        $form->addLabel("Edits the selected effect and saves it to the kit.");

        $form->addLabel("Effect: " . $effectInformation->getName());

        $form->addInput("Effect Duration (In Seconds)", strval($effect->getDuration() / 20));
        $form->addInput("Effect Strength (Amplifier)", strval($effect->getAmplifier()));

        $form->addExtraData("effect", $effect);
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
        return self::EDIT_KIT_EFFECT;
    }
}