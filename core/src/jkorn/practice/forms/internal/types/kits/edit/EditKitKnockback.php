<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-07-31
 * Time: 19:40
 */

declare(strict_types=1);

namespace jkorn\practice\forms\internal\types\kits\edit;


use jkorn\practice\forms\internal\InternalForm;
use jkorn\practice\forms\types\CustomForm;
use jkorn\practice\kits\IKit;
use jkorn\practice\kits\data\KitCombatData;
use pocketmine\Player;
use pocketmine\utils\TextFormat;


class EditKitKnockback extends InternalForm
{

    /**
     * @return string
     *
     * Gets the localized name of the practice form.
     */
    public function getLocalizedName(): string
    {
        return self::EDIT_KIT_KNOCKBACK;
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

        $form = new CustomForm(function(Player $player, $data, $extraData)
        {
            if($data !== null && isset($extraData["kit"]))
            {
                /** @var IKit $kit */
                $kit = $extraData["kit"];
                $combatInfo = $kit->getCombatData();

                $combatInfo->update(KitCombatData::HORIZONTAL_KB, $data[1]);
                $combatInfo->update(KitCombatData::VERTICAL_KB, $data[2]);
                $combatInfo->update(KitCombatData::ATTACK_DELAY, $data[3]);

                // TODO: Send message that kit's kb has been successfully updated.
            }
        });

        $knockback = $kit->getCombatData();

        $form->setTitle(TextFormat::BOLD . "Edit Knockback");

        $form->addLabel("Edit the knockback information of the kit.");
        $form->addInput("Horizontal (X, Z) Knockback", strval($knockback->getXZ()));
        $form->addInput("Vertical (Y) Knockback", strval($knockback->getY()));
        $form->addInput("Attack Delay", strval($knockback->getSpeed()));

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