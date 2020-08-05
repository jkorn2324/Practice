<?php

declare(strict_types=1);

namespace jkorn\ffa\forms\internal;


use jkorn\ffa\arenas\FFAArena;
use jkorn\ffa\FFAGameManager;
use jkorn\practice\forms\types\properties\ButtonTexture;
use jkorn\practice\forms\types\SimpleForm;
use jkorn\practice\PracticeCore;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class DeleteFFAArena extends FFAInternalForm
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

        $form = new SimpleForm(function(Player $player, $data, $extraData)
        {
            if($data !== null && isset($extraData["arena"]))
            {
                /** @var FFAArena|null $arena */
                $arena = $extraData["arena"];

                $ffaManager = PracticeCore::getBaseGameManager()->getGameManager(FFAGameManager::GAME_TYPE);
                if(!$ffaManager instanceof FFAGameManager)
                {
                    return;
                }

                $ffaManager->getArenaManager()->deleteArena($arena);

                // TODO: SEND MESSAGE
            }
        });

        $form->setTitle(TextFormat::BOLD . "Delete FFA Arena");

        $content = [
            "Are you sure you want to delete the ffa arena from the server?",
            "",
            "FFA Arena: " . $arena->getName(),
            "",
            "Select " . TextFormat::BOLD . "yes" . TextFormat::RESET . " to delete, or " . TextFormat::BOLD . "no" . TextFormat::RESET . " to cancel."
        ];

        $form->setContent(implode($content, "\n"));

        $form->addButton(TextFormat::BOLD . "Yes",
            new ButtonTexture(ButtonTexture::TYPE_PATH, "textures/ui/confirm.png"));
        $form->addButton(TextFormat::BOLD . "No",
            new ButtonTexture(ButtonTexture::TYPE_PATH, "textures/ui/cancel.png"));

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
        return self::FFA_ARENA_DELETE;
    }
}