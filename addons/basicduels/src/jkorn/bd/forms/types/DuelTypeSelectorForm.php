<?php

declare(strict_types=1);

namespace jkorn\bd\forms\types;


use jkorn\bd\BasicDuelsManager;
use jkorn\practice\forms\display\FormDisplay;
use jkorn\practice\forms\display\properties\FormDisplayText;
use jkorn\practice\forms\types\SimpleForm;
use jkorn\practice\PracticeCore;
use pocketmine\Player;

class DuelTypeSelectorForm extends FormDisplay
{

    /**
     * @param array $data - The input data.
     * Initializes the form data.
     */
    protected function initData(array &$data): void
    {
        $this->formData["title"] = new FormDisplayText($data["title"]);
        $this->formData["description"] = new FormDisplayText($data["description"]);

        $buttons = $data["buttons"];
        foreach ($buttons as $buttonLocal => $text) {
            $inputText = $text;
            if (is_array($text)) {
                $topLine = "";
                $bottomLine = "";
                if (isset($text["top.text"])) {
                    $topLine = $text["top.text"];
                }

                if (isset($text["bottom.text"])) {
                    $bottomLine = $text["bottom.text"];
                }

                if ($bottomLine !== "") {
                    $inputText = implode("\n", [$topLine, $bottomLine]);
                } else {
                    $inputText = $topLine;
                }
            }
            $this->formData["button.{$buttonLocal}"] = new FormDisplayText($inputText);
        }
    }

    /**
     * @param Player $player - The player we are sending the form to.
     *
     * Displays the form to the given player.
     */
    public function display(Player $player): void
    {
        $form = new SimpleForm(function(Player $player, $data, $extraData)
        {
            // TODO: Selector.
        });

        /** @var BasicDuelsManager|null $duelsManager */
        $duelsManager = PracticeCore::getBaseGameManager()->getGameManager(BasicDuelsManager::NAME);
        if($duelsManager === null)
        {
            return;
        }

        $gameTypes = $duelsManager->getGameTypes();
        if(count($gameTypes) <= 0)
        {
            $button = $this->formData["button.duel.button.none"]->getText($player);
            $form->addButton($button);
            $form->setExtraData(["gameTypes" => []]);
            $player->sendForm($form);
            return;
        }

        $inGameTypes = [];
        foreach($gameTypes as $gameType => $texture)
        {
            $button = $this->formData["button.duel.button.template"];
            if($texture !== "")
            {
                $form->addButton(
                    $button->getText($player, $gameType),
                    0,
                    $texture
                );
            }
            else
            {
                $form->addButton($button->getText($player, $gameType));
            }

            $inGameType[] = $gameType;
        }

        $form->setExtraData(["gameTypes" => $inGameTypes]);
        $player->sendForm($form);
    }

    /**
     * @param string $localized
     * @param array $data
     *
     * @return DuelTypeSelectorForm
     *
     * Decodes the form from the localized name and the data.
     */
    public static function decode(string $localized, array $data)
    {
        $title = "Basic Duel Selector";
        $description = "Select the type of duel you want to play.";
        $buttons = [
            "duel.button.template" => [
                "top.text" => "",
                "bottom.text" => ""
            ],
            "duel.button.none" => [
                "top.text" => "",
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

        return new DuelTypeSelectorForm($localized, [
            "title" => $title,
            "description" => $description,
            "buttons" => $buttons
        ]);
    }
}