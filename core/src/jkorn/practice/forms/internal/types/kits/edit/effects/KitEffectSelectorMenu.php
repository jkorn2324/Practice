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

class KitEffectSelectorMenu extends InternalForm
{

    /**
     * @return string
     *
     * Gets the localized name of the practice form.
     */
    public function getLocalizedName(): string
    {
        return self::EFFECT_KIT_SELECTOR_MENU;
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
            if($data !== null && isset($extraData["formType"], $extraData["kit"], $extraData["effects"]))
            {
                /** @var IKit $kit */
                $kit = $extraData["kit"];
                $action = strval($extraData["formType"]);
                /** @var EffectInstance[] $effects */
                $effects = $extraData["effects"];

                // Do nothing is the effects count is less than zero.
                if(count($effects) <= 0)
                {
                    return;
                }

                /** @var EffectInstance $effect */
                $effect = $effects[(int)$data];
                $form = InternalForm::getForm($action);
                if($form !== null)
                {
                    $form->display($player, $kit, $effect);
                }
            }
        });

        $form->setTitle(TextFormat::BOLD . "Select Effect");
        $form->setContent("Select the effect to add/remove/edit.");

        // Sets the effects of the form.
        if(isset($outputMenu) && $outputMenu !== self::ADD_KIT_EFFECT) {
            $this->setEffects($form, $kit);
        } else {
            $this->setEffects($form);
        }

        $form->addExtraData("kit", $kit);
        if(isset($outputMenu))
        {
            $form->addExtraData("formType", $outputMenu);
        }
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
     * @param SimpleForm $form - The form to add the effects to.
     * @param IKit|null $kit
     *
     * Sets the effects in the simple form.
     */
    private function setEffects(SimpleForm &$form, ?$kit = null): void
    {
        // Determines that the effects are from the kit and not from the effects data.
        if($kit !== null)
        {
            $inputEffects = [];
            $effects = $kit->getEffectsData()->getEffects();
            if(count($effects) <= 0)
            {
                $form->addButton("None");
                $form->addExtraData("effects", []);
                return;
            }

            foreach($effects as $effect)
            {
                if($effect instanceof EffectInstance)
                {
                    $effectInformation = EffectInformation::getInformation($effect->getId());
                    if($effectInformation !== null)
                    {
                        $texture = $effectInformation->getFormTexture();
                        if($texture !== "")
                        {
                            $form->addButton($effectInformation->getName(), 0, $texture);
                        }
                        else
                        {
                            $form->addButton($effectInformation->getName());
                        }
                        $inputEffects[] = $effect;
                    }
                }
            }
            $form->addExtraData("effects", $inputEffects);
            return;
        }

        // Adds the effects to the selector menu.
        $effects = [];
        $effectsInformation = EffectInformation::getAll();
        foreach($effectsInformation as $effectInformation)
        {
            $instance = $effectInformation->createInstance();
            if($instance !== null)
            {
                $texture = $effectInformation->getFormTexture();
                if($texture !== "")
                {
                    $form->addButton($effectInformation->getName(), 0, $texture);
                }
                else
                {
                    $form->addButton($effectInformation->getName());
                }

                $effects[] = $instance;
            }
        }

        $form->addExtraData("effects", $effects);
    }
}