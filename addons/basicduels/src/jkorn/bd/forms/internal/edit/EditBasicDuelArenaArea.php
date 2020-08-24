<?php

declare(strict_types=1);

namespace jkorn\bd\forms\internal\edit;


use jkorn\bd\arenas\PreGeneratedDuelArena;
use jkorn\bd\forms\internal\BasicDuelInternalForm;
use jkorn\practice\forms\types\CustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class EditBasicDuelArenaArea extends BasicDuelInternalForm
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
            || !$arena instanceof PreGeneratedDuelArena
        )
        {
            return;
        }

        // No Callable as we aren't doing anything here.
        $form = new CustomForm();

        $form->setTitle(TextFormat::BOLD . "Edit Basic Duel Arena");
        $form->addLabel("Edit the basic duel arena area.");

        $form->addLabel("Command to edit the 1st Player's Spawn Position:\n" . TextFormat::BOLD . "/bdarena spawnPos1 " . $arena->getName());
        $form->addLabel("Command to edit the 2nd Player's Spawn Position:\n" . TextFormat::BOLD . "/bdarena spawnPos2 " . $arena->getName());

        $form->addLabel("Command to edit the 1st Bound of the Arena:\n" . TextFormat::BOLD . "/bdarena pos1 " . $arena->getName());
        $form->addLabel("Command to edit the 2nd Bound of the Arena:\n" . TextFormat::BOLD . "/bdarena pos2 " . $arena->getName());
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
        return self::EDIT_BASIC_DUEL_ARENA_AREA;
    }
}