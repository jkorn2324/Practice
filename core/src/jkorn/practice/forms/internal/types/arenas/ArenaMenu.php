<?php

declare(strict_types=1);

namespace jkorn\practice\forms\internal\types\arenas;


use jkorn\practice\arenas\PracticeArenaManager;
use jkorn\practice\forms\internal\InternalForm;
use jkorn\practice\forms\types\SimpleForm;
use jkorn\practice\PracticeCore;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ArenaMenu extends InternalForm
{

    /**
     * @param Player $player
     * @param mixed ...$args
     *
     * Called when the display method first occurs.
     */
    protected function onDisplay(Player $player, ...$args): void
    {
        $form = new SimpleForm(function(Player $player, $data, $extraData)
        {
            if($data !== null && isset($extraData["managers"]))
            {
                /** @var PracticeArenaManager[] $managers */
                $managers = $extraData["managers"];
                if(count($managers) <= 0)
                {
                    return;
                }

                if(isset($managers[(int)$data]))
                {
                    $manager = $managers[(int)$data];
                    $form = $manager->getArenaEditorMenu();
                    if($form !== null)
                    {
                        $form->display($player);
                    }
                }
            }
        });

        $form->setTitle(TextFormat::BOLD . "Arena Menu Selector");
        $form->setContent("Select the arena type you want to edit.");

        $arenaManagers = PracticeCore::getBaseGameManager()->getArenaManagers();
        if(count($arenaManagers) <= 0)
        {
            $form->addButton("None");
            $form->addExtraData("managers", []);
            $player->sendForm($form);
            return;
        }

        $inManagers = [];
        foreach($arenaManagers as $manager)
        {
            $form->addButton($manager->getFormDisplayName(), $manager->getFormButtonTexture());
            $inManagers[] = $manager;
        }

        $form->addExtraData("managers", $inManagers);
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
        return self::ARENA_MENU_MANAGER;
    }
}