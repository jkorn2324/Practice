<?php

declare(strict_types=1);

namespace jkorn\practice\forms\internal\types\kits;


use jkorn\practice\forms\internal\IInternalForm;
use jkorn\practice\forms\internal\InternalForms;
use jkorn\practice\forms\types\SimpleForm;
use jkorn\practice\kits\IKit;
use jkorn\practice\player\PracticePlayer;
use jkorn\practice\PracticeCore;
use pocketmine\Player;

class KitSelectorMenu implements IInternalForm
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

        // Gets the form type.
        $formType = isset($args[0]) ? $args[1] : self::VIEW_KIT_FORM;

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

            $type = self::VIEW_KIT_FORM;
            if(isset($extraData["formType"]))
            {
                $type = $extraData["formType"];
            }

            /** @var IKit[] $kits */
            $kits = [];
            if(isset($extraData["kits"]))
            {
                $kits = $extraData["kits"];
            }

            if(
                count($kits) <= 0
                || $data === null
            )
            {
                return;
            }

            $kit = $kits[(int)$data];
            $form = InternalForms::getForm($type);
            if($form !== null)
            {
                $form->display($player, $kit);
            }

            // TODO

        });

        $form->setTitle("Select Kit");
        $form->setContent("Select the kit to edit or delete.");

        $kits = PracticeCore::getKitManager()->getAll();
        if(count($kits) <= 0)
        {
            $form->addButton("None");
            $form->addExtraData("kits", []);
            $form->addExtraData("formType", $formType);
            $player->sendForm($form);
            return;
        }

        $inputKits = [];
        foreach($kits as $kit)
        {
            $texture = $kit->getTexture();
            if($texture !== "")
            {
                $form->addButton($kit->getName(), 0, $texture);
            }
            else
            {
                $form->addButton($kit->getName());
            }
            $inputKits[] = $kit;
        }
        $form->addExtraData("kits", $inputKits);
        $form->addExtraData("formType", $formType);
        $player->sendForm($form);
    }

    /**
     * @return string
     *
     * Gets the localized name of the practice form.
     */
    public function getLocalizedName(): string
    {
        return self::KIT_SELECTOR;
    }
}