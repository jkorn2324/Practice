<?php

declare(strict_types=1);

namespace jkorn\practice\forms\internal\types\kits;


use jkorn\practice\forms\internal\InternalForm;
use jkorn\practice\forms\types\SimpleForm;
use jkorn\practice\kits\IKit;
use jkorn\practice\PracticeCore;
use pocketmine\utils\TextFormat;
use pocketmine\Player;

class KitSelectorMenu extends InternalForm
{

    /**
     * @return string
     *
     * Gets the localized name of the practice form.
     */
    public function getLocalizedName(): string
    {
        return self::KIT_SELECTOR;
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
        // Gets the form type.
        $formType = isset($args[0]) ? $args[0] : self::VIEW_KIT_FORM;

        $form = new SimpleForm(function(Player $player, $data, $extraData)
        {
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
            $form = InternalForm::getForm($type);
            if($form !== null)
            {
                $form->display($player, $kit);
            }
        });

        $form->setTitle(TextFormat::BOLD . "Select Kit");
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
            $form->addButton($kit->getName(), $kit->getFormButtonTexture());
            $inputKits[] = $kit;
        }
        $form->addExtraData("kits", $inputKits);
        $form->addExtraData("formType", $formType);
        $player->sendForm($form);
    }
}