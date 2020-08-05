<?php

declare(strict_types=1);

namespace jkorn\ffa\forms\types;


use jkorn\ffa\FFAGameManager;
use jkorn\ffa\games\FFAGame;
use jkorn\ffa\statistics\FFADisplayStatistics;
use jkorn\practice\forms\display\FormDisplay;
use jkorn\practice\forms\display\FormDisplayText;
use jkorn\practice\forms\types\SimpleForm;
use jkorn\practice\games\misc\gametypes\IGame;
use jkorn\practice\PracticeCore;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

/**
 * Class PlayFFAForm
 * @package jkorn\ffa\forms\types
 *
 * The display for the Play FFA Form.
 */
class PlayFFAForm extends FormDisplay
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
            $text = "";
            if(isset($data["top.text"]))
            {
                $text = $data["top.text"];
            }

            if(isset($data["bottom.text"]) && trim($data["bottom.text"]) !== "")
            {
                $text .= "\n" . $data["bottom.text"];
            }

            $this->formData["button.{$buttonLocal}"] = new FormDisplayText($text);
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
        $gameManager = PracticeCore::getBaseGameManager()->getGameManager(FFAGameManager::GAME_TYPE);
        if(!$gameManager instanceof FFAGameManager)
        {
            return;
        }

        $form = new SimpleForm(function(Player $player, $data, $extraData)
        {
            if($data !== null && isset($extraData["games"]))
            {
                /** @var IGame[] $games */
                $games = $extraData["games"];
                if(count($games) <= 0)
                {
                    return;
                }

                if(isset($games[$data]))
                {
                    $game = $games[$data];
                    if($game instanceof FFAGame)
                    {
                        $game->putInGame($player);
                    }
                }
            }
        });

        $form->setTitle($this->formData["title"]->getText($player));
        $form->setContent($this->formData["description"]->getText($player));

        $games = $gameManager->getGames(function(FFAGame $game)
        {
            return $game->validate();
        });

        if(count($games) <= 0)
        {
            $form->addButton(
                $this->formData["button.select.arena.none"]->getText($player)
            );
            $form->setExtraData(["games" => []]);
            $player->sendForm($form);
            return;
        }

        $inputGames = [];
        foreach($games as $game)
        {
            $form->addButton(
                $this->formData["button.select.arena.template"]->getText($player, $game),
                $game->getFormButtonTexture()
            );
            $inputGames[] = $game;
        }

        $form->setExtraData(["games" => $inputGames]);
        $player->sendForm($form);
    }

    /**
     * @param string $localized
     * @param array $data
     * @return PlayFFAForm
     *
     * Decodes the FFA play form & creates an object.
     */
    public static function decode(string $localized, array $data)
    {
        $title = TextFormat::BOLD . "Play FFA";
        $description = "Select the arena that you want to play.";

        $buttons = [
            "select.arena.template" => [
                "top.text" => "{" . FFADisplayStatistics::STATISTIC_FFA_ARENA_NAME . "}",
                "bottom.text" => "Players: {" . FFADisplayStatistics::STATISTIC_FFA_ARENA_PLAYERS_PLAYING . "}"
            ],
            "select.arena.none" => [
                "top.text" => "None",
                "bottom.text" => ""
            ]
        ];

        if(isset($data["title"]))
        {
            $title = (string)$data["title"];
        }

        if(isset($data["description"]))
        {
            $description = (string)$data["description"];
        }

        if(isset($data["buttons"]))
        {
            $buttons = array_replace($buttons, $data["buttons"]);
        }

        return new PlayFFAForm($localized, [
            "title" => $title,
            "description" => $description,
            "buttons" => $buttons
        ]);
    }
}