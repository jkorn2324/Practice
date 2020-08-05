<?php

declare(strict_types=1);

namespace jkorn\practice\forms\display\types;


use jkorn\practice\forms\display\FormDisplay;
use jkorn\practice\forms\display\FormDisplayText;
use jkorn\practice\forms\types\SimpleForm;
use jkorn\practice\PracticeCore;
use pocketmine\Player;

class SpectatorSelectionForm extends FormDisplay
{


    /**
     * @param Player $player
     * @param mixed ...$args
     *
     * Displays the form to the player.
     */
    public function display(Player $player, ...$args): void
    {
        $form = new SimpleForm(function(Player $player, $data, $extraData)
        {
            // TODO: Get output.
        });

        $form->setTitle($this->formData["title"]->getText($player));
        $form->setContent($this->formData["description"]->getText($player));

        $games = PracticeCore::getBaseGameManager()->getSpectatingGames();

        if(count($games) <= 0)
        {
            $form->addButton("None");
            $form->setExtraData(["games" => []]);
            $player->sendForm($form);
            return;
        }

        $inputGames = [];
        foreach($games as $game)
        {
            $form->addButton($game->getSpectatorFormDisplayName($player), $game->getSpectatorFormButtonTexture());
            $inputGames[] = $game;
        }

        $form->setExtraData(["games" => $inputGames]);
        $player->sendForm($form);

    }

    /**
     * @param array $data - The input data.
     * Initializes the form data.
     */
    protected function initData(array &$data): void
    {
        $this->formData["title"] = new FormDisplayText($data["title"]);
        $this->formData["description"] = new FormDisplayText($data["description"]);
    }

    /**
     * @param string $localized
     * @param array $data
     *
     * @return SpectatorSelectionForm
     *
     * Decodes the information and gets a spectator selector form object.
     */
    public static function decode(string $localized, array $data)
    {
        $title = "Spectate Game";
        $description = "Select the game that you want to spectate.";

        if(isset($data["title"]))
        {
            $title = $data["title"];
        }

        if(isset($data["description"]))
        {
            $description = $data["description"];
        }

        return new SpectatorSelectionForm($localized, [
                "title" => $title,
                "description" => $description
        ]);
    }
}