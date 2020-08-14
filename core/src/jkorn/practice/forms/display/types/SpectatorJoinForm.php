<?php

declare(strict_types=1);

namespace jkorn\practice\forms\display\types;


use jkorn\practice\forms\display\ButtonDisplayText;
use jkorn\practice\forms\display\FormDisplay;
use jkorn\practice\forms\display\FormDisplayText;
use jkorn\practice\forms\types\SimpleForm;
use jkorn\practice\games\misc\gametypes\ISpectatorGame;
use jkorn\practice\player\PracticePlayer;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class SpectatorJoinForm extends FormDisplay
{

    /**
     * @param array $data - The input data.
     * Initializes the form data.
     */
    protected function initData(array &$data): void
    {
        $this->formData["description"] = new FormDisplayText($data["description"]);
        $this->formData["title"] = new FormDisplayText($data["title"]);

        $buttons = $data["buttons"];
        foreach($buttons as $buttonLocal => $data)
        {
            $formData = ButtonDisplayText::decode($data);
            if($formData !== null)
            {
                $this->formData["button.{$buttonLocal}"] = $formData;
            }
        }
    }

    /**
     * @param string $localized
     * @param array $data
     *
     * @return SpectatorJoinForm
     *
     * Decodes the Spectator Join form based on localized name & data.
     *
     */
    public static function decode(string $localized, array $data)
    {
        $description = "Select \"Join\" if you want to spectate the game, otherwise select \"Cancel\".";
        $title = TextFormat::BOLD . "Spectate Game";
        $buttons = [
            "selection.join" => [
                "top.text" => TextFormat::BOLD . "Join",
                "bottom.text" => "",
            ],
            "selection.cancel" => [
                "top.text" => TextFormat::BOLD . "Cancel",
                "bottom.text" => ""
            ]
        ];

        if(isset($data["title"]))
        {
            $title = $data["title"];
        }

        if(isset($data["description"]))
        {
            $description = $data["description"];
        }

        if(isset($data["buttons"]))
        {
            $buttons = array_replace($buttons, $data["buttons"]);
        }

        return new SpectatorJoinForm($localized, [
            "title" => $title,
            "description" => $description,
            "buttons" => $buttons
        ]);
    }

    /**
     * @param Player $player
     * @param mixed ...$args
     *
     * Displays the form to the player.
     */
    public function display(Player $player, ...$args): void
    {
        // TODO: Deal with spectating and awaitng games.
        if
        (
            !$player instanceof PracticePlayer
            || $player->isInGame()
            || $player->isSpectatingGame()
        )
        {
            return;
        }

        // Do nothing if the game isn't an ispectator game.
        if
        (
            !isset($args[0])
            || ($game = $args[0]) === null
            || !$game instanceof ISpectatorGame
        )
        {
            return;
        }

        $form = new SimpleForm(function(Player $player, $data, $extraData)
        {
            // TODO: Handle result
        });

        $form->setTitle($this->formData["title"]->getText($player));

        $description = $game->getGameDescription() . "\n" . $this->formData["description"]->getText($player, $game);
        $form->setContent($description);

        $form->addButton($this->formData["button.selection.join"]->getText($player, $game), 0, "textures/ui/confirm.png");
        $form->addButton($this->formData["button.selection.cancel"]->getText($player, $game), 0, "textures/ui/cancel.png");

        $player->sendForm($form);
    }
}