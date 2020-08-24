<?php

declare(strict_types=1);

namespace jkorn\bd\forms\internal\edit;


use jkorn\bd\arenas\PreGeneratedDuelArena;
use jkorn\bd\forms\internal\BasicDuelInternalForm;
use jkorn\practice\forms\types\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class EditBasicDuelArenaVisibility extends BasicDuelInternalForm
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

        $form = new SimpleForm(function(Player $player, $data, $extraData)
        {
            if($data !== null && isset($extraData["arena"]))
            {
                /** @var PreGeneratedDuelArena $arena */
                $arena = $extraData["arena"];
                $visible = $data === 0 ? true : false;
                $arena->setVisible($visible);

                // TODO: Send message.
            }
        });

        $form->setTitle(TextFormat::BOLD . "Edit Arena Visibility");

        $content = [
            "This sets the arena as either open or closed. Essentially determines whether or not players are able to join that arena in a duel.",
            "",
            "Arena: " . $arena->getName(),
            "",
            "Select \"Open\" to set the arena as open, otherwise select \"Closed\" to close the arena and prevent duels from occuring there.",
            ""
        ];

        $form->setContent(implode("\n", $content));

        $form->addButton(TextFormat::BOLD . "Open", 0, "textures/ui/confirm.png");
        $form->addButton(TextFormat::BOLD . "Close", 0, "textures/ui/cancel.png");

        $form->addButton("arena", $arena);

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
        return self::EDIT_BASIC_DUEL_ARENA_VISIBILITY;
    }
}