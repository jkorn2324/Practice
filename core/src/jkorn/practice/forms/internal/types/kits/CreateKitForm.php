<?php

declare(strict_types=1);

namespace jkorn\practice\forms\internal\types\kits;


use jkorn\practice\forms\internal\InternalForm;
use jkorn\practice\forms\types\CustomForm;
use jkorn\practice\kits\data\KitCombatData;
use jkorn\practice\kits\KitEffectsData;
use jkorn\practice\kits\SavedKit;
use jkorn\practice\PracticeCore;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class CreateKitForm extends InternalForm
{

    /**
     * @return string
     *
     * Gets the localized name of the practice form.
     */
    public function getLocalizedName(): string
    {
        return self::CREATE_KIT_FORM;
    }

    /**
     * @param Player $player
     * @param mixed ...$args
     *
     * Called when the display method first occurs.
     */
    protected function onDisplay(Player $player, ...$args): void
    {
        $form = new CustomForm(function(Player $player, $data, $extraData)
        {
            // TODO
            if($data !== null)
            {
                /** @var string $kitName */
                $kitName = trim(TextFormat::clean($data[1]));

                if(strpos($kitName, ' ') !== false)
                {
                    // TODO: Send message that the name can't have spaces.
                    return;
                }

                $savedKit = new SavedKit(
                    $kitName,
                    $player->getInventory()->getContents(true),
                    $player->getArmorInventory()->getContents(true),
                    new KitEffectsData(),
                    new KitCombatData(0.4, 0.4, 10),
                    ""
                );

                if(PracticeCore::getKitManager()->add($savedKit))
                {
                    // TODO: Send message saying player added the kit.
                }
                else
                {
                    // TODO: Send message saying the kit already exists.
                }
            }
        });

        $form->setTitle(TextFormat::BOLD . "Create New Kit");
        $form->addLabel("This option creates a new kit from the items in your inventory and the effects currently on your player.");

        $form->addInput("Please provide the name of the kit that you want to create:");

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