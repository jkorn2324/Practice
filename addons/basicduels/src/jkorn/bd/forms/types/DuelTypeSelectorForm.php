<?php

declare(strict_types=1);

namespace jkorn\bd\forms\types;


use jkorn\bd\BasicDuelsManager;
use jkorn\bd\duels\types\BasicDuelGameInfo;
use jkorn\bd\forms\BasicDuelsFormManager;
use jkorn\practice\forms\display\FormDisplay;
use jkorn\practice\forms\display\FormDisplayText;
use jkorn\practice\forms\types\SimpleForm;
use jkorn\practice\player\PracticePlayer;
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
            $formData = FormDisplayText::decodeButton($text);
            if($formData !== null)
            {
                $this->formData["button.{$buttonLocal}"] = $formData;
            }
        }
    }

    /**
     * @param Player $player - The player we are sending the form to.
     * @param mixed...$args
     *
     * Displays the form to the given player.
     */
    public function display(Player $player, ...$args): void
    {
        $form = new SimpleForm(function(Player $player, $data, $extraData)
        {
            /** @var BasicDuelGameInfo[] $gameTypes */
            $gameTypes = $extraData["gameTypes"];
            if(count($gameTypes) <= 0)
            {
                return;
            }

            if($data !== null)
            {
                $gameType = $gameTypes[(int)$data];
                if(
                    $player instanceof PracticePlayer
                    && !$player->isInGame()
                )
                {
                    $formDisplayManager = PracticeCore::getBaseFormDisplayManager()->getFormManager(BasicDuelsFormManager::NAME);
                    if($formDisplayManager !== null)
                    {
                        $form = $formDisplayManager->getForm(BasicDuelsFormManager::KIT_SELECTOR_FORM);
                        if($form !== null)
                        {
                            $form->display($player, $gameType);
                        }
                    }
                }
            }
        });

        /** @var BasicDuelsManager|null $duelsManager */
        $duelsManager = PracticeCore::getBaseGameManager()->getGameManager(BasicDuelsManager::NAME);
        if($duelsManager === null)
        {
            return;
        }

        $form->setTitle($this->formData["title"]->getText($player));
        $form->setContent($this->formData["description"]->getText($player));

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
        foreach($gameTypes as $localized => $gameType)
        {
            $button = $this->formData["button.duel.button.template"];
            $form->addButton($button->getText($player, $gameType), $gameType->getFormButtonTexture());

            $inGameTypes[] = $gameType;
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