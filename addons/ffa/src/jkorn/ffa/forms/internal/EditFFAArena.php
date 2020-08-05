<?php

declare(strict_types=1);

namespace jkorn\ffa\forms\internal;

use jkorn\ffa\arenas\FFAArena;
use jkorn\practice\forms\types\CustomForm;
use jkorn\practice\kits\IKit;
use jkorn\practice\PracticeCore;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class EditFFAArena extends FFAInternalForm
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
            || ($arena = $args[0]) === null
            || !$arena instanceof FFAArena
        )
        {
            return;
        }

        $form = new CustomForm(function(Player $player, $data, $extraData)
        {
            if($data !== null && isset($extraData["kits"], $extraData["arena"]))
            {
                /** @var FFAArena $arena */
                $arena = $extraData["arena"];

                /** @var IKit[] $kits */
                $kits = $extraData["kits"];

                if(count($kits) <= 0)
                {
                    return;
                }

                $kitIndex = $data[2];

                // Sets the kit as null or the index.
                if(isset($kits[$kitIndex])) {
                    $kit = $kits[$kitIndex];
                } else {
                    $kit = null;
                }

                $arena->setKit($kit);
            }
        });

        $form->setTitle(TextFormat::BOLD . "Edit FFA Arena");
        $form->addLabel("Edit the ffa arena's information.");
        $form->addLabel("Arena: " . $arena->getName());

        $kits = PracticeCore::getKitManager()->getAll();

        // Gets the kits.
        $kitOptions = []; $inKits = [];
        foreach($kits as $kit)
        {
            if($kit->equals($arena->getKit()))
            {
                $default = count($kitOptions);
            }
            $inKits[] = $kit;
            $kitOptions[] = $kit->getName();
        }

        if(!isset($default))
        {
            $default = count($kitOptions);
        }

        $kitOptions[] = "None";

        $form->addDropdown("Edit the kit of the arena", $kitOptions, $default);

        $form->addLabel("To set the spawn position of this arena, run the command:\n" . TextFormat::RED . "/ffa spawn " . $arena->getName() . TextFormat::RESET);
        $form->addLabel("To set the first spawn protection boundary of this arena, run the command:\n" . TextFormat::RED . "/ffa pos1Spawn " . $arena->getName() . TextFormat::RESET);
        $form->addLabel("To set the second spawn protection boundary of this arena, run the command:\n" . TextFormat::RED . "/ffa pos2Spawn " . $arena->getName() . TextFormat::RESET);

        // Sets the extra data.
        $form->addExtraData("kits", $inKits);
        $form->addExtraData("arena", $arena);

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
        return self::FFA_ARENA_EDIT;
    }
}