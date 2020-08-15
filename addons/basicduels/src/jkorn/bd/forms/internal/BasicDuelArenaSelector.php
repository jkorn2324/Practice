<?php

declare(strict_types=1);

namespace jkorn\bd\forms\internal;


use jkorn\bd\arenas\ArenaManager;
use jkorn\bd\arenas\PreGeneratedDuelArena;
use jkorn\bd\BasicDuelsManager;
use jkorn\practice\forms\internal\InternalForm;
use jkorn\practice\forms\types\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class BasicDuelArenaSelector extends BasicDuelInternalForm
{

    /**
     * @param Player $player
     * @param mixed ...$args
     *
     * Called when the display method first occurs.
     */
    protected function onDisplay(Player $player, ...$args): void
    {
        $ffaManager = $this->getGameManager();
        if(!$ffaManager instanceof BasicDuelsManager)
        {
            return;
        }

        /** @var ArenaManager $arenaManager */
        $arenaManager = $ffaManager->getArenaManager();

        $form = new SimpleForm(function(Player $player, $data, $extraData)
        {
            if($data !== null && isset($extraData["arenas"], $extraData["formType"]))
            {
                /** @var PreGeneratedDuelArena[] $arenas */
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

        $form->setTitle(TextFormat::BOLD . "Select Basic Duel Arena");
        $form->setContent("Select the Basic Duel Arena you want to edit or delete.");

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
            $form->addButton($arena->getName(), $arena->getFormButtonTexture());
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
        return self::BASIC_DUEL_ARENA_SELECTOR;
    }
}