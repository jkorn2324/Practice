<?php

declare(strict_types=1);

namespace jkorn\bd\forms\internal\edit;


use jkorn\bd\arenas\PreGeneratedDuelArena;
use jkorn\bd\forms\internal\BasicDuelInternalForm;
use jkorn\practice\forms\internal\InternalForm;
use jkorn\practice\forms\types\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class EditBasicDuelArenaMenu extends BasicDuelInternalForm
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

                switch((int)$data)
                {
                    case 0:
                        $form = InternalForm::getForm(self::EDIT_BASIC_DUEL_ARENA_KITS_MENU);
                        break;
                    case 1:
                        $form = InternalForm::getForm(self::EDIT_BASIC_DUEL_ARENA_AREA);
                        break;
                    case 2:
                        $form = InternalForm::getForm(self::EDIT_BASIC_DUEL_ARENA_VISIBILITY);
                        break;
                }

                if(isset($form) && $form instanceof InternalForm)
                {
                    $form->display($player, $arena);
                }
            }
        });

        $form->setTitle(TextFormat::BOLD . "Edit Basic Duel Arena");
        $form->setContent("Edit the Basic Duel Arena's duel kits, information, and visibility.\nArena: {$arena->getName()}");

        $form->addButton(TextFormat::BOLD . "Edit Duel Kits", 0, "textures/ui/icon_armor.png");
        $form->addButton(TextFormat::BOLD . "Edit Arena Area", 0, "textures/ui/icon_recipe_nature.png");
        $form->addButton(TextFormat::BOLD . "Edit Arena Visibility", 0, "textures/ui/magnifying_glass.png");

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
        return self::EDIT_BASIC_DUEL_ARENA_MENU;
    }
}