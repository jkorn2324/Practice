<?php

declare(strict_types=1);

namespace jkorn\ffa\forms\internal;


use jkorn\ffa\arenas\FFAArena;
use jkorn\ffa\arenas\FFAArenaManager;
use jkorn\practice\forms\internal\InternalForm;
use jkorn\practice\forms\types\SimpleForm;
use jkorn\practice\PracticeCore;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class FFAArenaSelector extends InternalForm implements FFAInternalForms
{

    /**
     * @param Player $player
     * @param mixed ...$args
     *
     * Called when the display method first occurs.
     */
    protected function onDisplay(Player $player, ...$args): void
    {

        $arenaManager = PracticeCore::getBaseArenaManager()->getArenaManager(FFAArenaManager::MANAGER_TYPE);
        if(!$arenaManager instanceof FFAArenaManager)
        {
            return;
        }

        $form = new SimpleForm(function(Player $player, $data, $extraData)
        {
            if($data !== null && isset($extraData["arenas"], $extraData["formType"]))
            {
                /** @var FFAArena[] $arenas */
                $arenas = $extraData["arenas"];
                $formType = strval($extraData["formType"]);

                if(isset($arenas[(int)$data]))
                {
                    $arena = $arenas[(int)$data];
                    $form = InternalForm::getForm($formType);

                    if($form !== null)
                    {
                        $form->display($player, $arena);
                    }
                }
            }
        });

        $form->setTitle(TextFormat::BOLD . "Select FFA Arena");
        $form->setContent("Select the FFA Arena you want to edit or delete.");

        $arenas = $arenaManager->getArenas();
        if(count($arenas) <= 0)
        {
            $form->addButton("None");
            $form->addExtraData("arenas", []);
            $player->sendForm($form);
            return;
        }

        $inArena = [];
        foreach($arenas as $arena)
        {
            $texture = $arena->getFormTexture();

            if($texture !== "")
            {
                $form->addButton($arena->getName(), 0, $texture);
            }
            else
            {
                $form->addButton($arena->getName());
            }
            $inArena[] = $arena;
        }

        $form->addExtraData("arenas", $inArena);
        if(isset($args[0]))
        {
            $form->addExtraData("formType", $args[0]);
        }

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
        return self::FFA_ARENA_SELECTOR;
    }
}