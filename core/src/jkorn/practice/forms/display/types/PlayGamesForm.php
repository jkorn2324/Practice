<?php

declare(strict_types=1);

namespace jkorn\practice\forms\display\types;


use jkorn\practice\forms\display\ButtonDisplayText;
use jkorn\practice\forms\display\FormDisplay;
use jkorn\practice\forms\display\FormDisplayText;
use jkorn\practice\forms\types\SimpleForm;
use jkorn\practice\games\misc\managers\IGameManager;
use jkorn\practice\PracticeCore;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class PlayGamesForm extends FormDisplay
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
     * @param Player $player - The player we are sending the form to.
     * @param mixed ...$args
     *
     * Displays the form to the given player.
     */
    public function display(Player $player, ...$args): void
    {
        $form = new SimpleForm(function(Player $player, $data, $extraData)
        {
            /** @var IGameManager[] $games */
            $games = $extraData["games"];
            if(count($games) <= 0)
            {
                return;
            }

            if($data !== null && isset($games[(int)$data]))
            {
                $gameManager = $games[(int)$data];
                $gameManager->onGameSelected($player);
            }
        });

        $gameTypes = PracticeCore::getBaseGameManager()->getGameManagers();

        $form->setTitle($this->formData["title"]->getText($player));
        $form->setContent($this->formData["description"]->getText($player));

        if(count($gameTypes) <= 0)
        {
            $form->addButton(
                $this->formData["button.select.game.none"]->getText($player)
            );
            $form->setExtraData(["games" => []]);
            $player->sendForm($form);
            return;
        }

        $inputGameTypes = [];
        foreach($gameTypes as $gameType)
        {
            $form->addButton(
                $this->formData["button.select.game.template"]->getText($player, $gameType),
                $gameType->getFormButtonTexture()
            );

            $inputGameTypes[] = $gameType;
        }

        $form->setExtraData(["games" => $inputGameTypes]);
        $player->sendForm($form);
    }

    /**
     * @param string $localized
     * @param array $data
     * @return PlayGamesForm
     *
     * Decodes the Play Games Form & creates an object.
     */
    public static function decode(string $localized, array $data)
    {
        $title = TextFormat::BOLD . "Play Games";
        $description = "Select the games that you want to play.";
        $buttons = [
            "select.game.template" => [
                "top.text" => "",
                "bottom.text" => ""
            ],
            "select.game.none" => [
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

        return new PlayGamesForm($localized,
        [
            "title" => $title,
            "description" => $description,
            "buttons" => $buttons
        ]);
    }
}