<?php

declare(strict_types=1);

namespace jkorn\ffa\forms\internal;


use jkorn\ffa\arenas\FFAArena;
use jkorn\ffa\FFAGameManager;
use jkorn\practice\forms\types\CustomForm;
use jkorn\practice\kits\IKit;
use jkorn\practice\level\SpawnArea;
use jkorn\practice\PracticeCore;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class CreateFFAArena extends FFAInternalForm
{

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
            if($data !== null && isset($extraData["kits"]))
            {
                $ffaManager = PracticeCore::getBaseGameManager()->getGameManager(FFAGameManager::GAME_TYPE);
                if(!$ffaManager instanceof FFAGameManager)
                {
                    return;
                }

                $arenaManager = $ffaManager->getArenaManager();

                // Gets the name of the FFA Arena.
                $name = strval($data[1]);

                // Sets the kit as null.
                $kit = null;

                /** @var IKit[] $kits */
                $kits = $extraData["kits"];
                if(count($kits) > 0 && isset($data[2]))
                {
                    $kitIndex = $data[2];
                    if(isset($kits[$kitIndex]))
                    {
                        $kit = $kits[$kitIndex];
                    }
                }

                $arena = new FFAArena(
                    $name,
                    $player->getLevelNonNull(),
                    new SpawnArea($player->asVector3()),
                    $kit
                );

                if($arenaManager->addArena($arena))
                {
                    // TODO: Send message that adds the arena.
                }
                else
                {
                    // TODO: Send message that creation failed.
                }
            }
        });

        $form->setTitle(TextFormat::BOLD . "Create Arena");
        $form->addLabel("This creates a new ffa arena, setting the spawn based on your current position.");

        $form->addInput("Please provide the name of the arena that you want to create:");

        $inKits = []; $kitOptions = [];
        $kits = PracticeCore::getKitManager()->getAll();
        if(count($kits) <= 0)
        {
            $form->addExtraData("kits", []);
            $player->sendForm($form);
            return;
        }

        foreach($kits as $kit)
        {
            $inKits[] = $kit;
            $kitOptions[] = $kit->getName();
        }

        $form->addDropdown("Please provide the kit you want to assign to this arena:", $kitOptions);
        $form->addExtraData("kits", $inKits);
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
        return self::FFA_ARENA_CREATE;
    }
}