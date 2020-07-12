<?php

declare(strict_types=1);

namespace practice\forms\display\types;


use pocketmine\Player;
use pocketmine\utils\TextFormat;
use practice\arenas\ArenaManager;
use practice\arenas\types\FFAArena;
use practice\forms\display\FormDisplay;
use practice\forms\display\FormDisplayText;
use practice\forms\display\statistics\FormDisplayStatistic;
use practice\forms\types\SimpleForm;
use practice\kits\Kit;
use practice\PracticeCore;

class FFAPlayForm extends FormDisplay
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
     *
     * Displays the form to the given player.
     */
    public function display(Player $player): void
    {
        $form = new SimpleForm(function(Player $player, $data, $extraData)
        {

        });

        $form->setTitle($this->formData["title"]->getText($player));
        $form->setContent($this->formData["description"]->getText($player));

        $arenas = PracticeCore::getArenaManager()->getArenas(ArenaManager::ARENA_TYPE_FFA);
        $inputArenas = [];

        foreach($arenas as $arena)
        {
            if($arena instanceof FFAArena)
            {
                $kit = $arena->getKit();
                $texture = $kit instanceof Kit ? $kit->getTexture() : null;

                $form->addButton(
                    $this->formData["button.select.arena.template"]->getText($player, $arena),
                    $texture !== null ? 0 : -1,
                    $texture
                );

                $inputArenas[] = $arena;
            }
        }

        $form->setExtraData(["arenas" => $inputArenas]);
    }

    /**
     * @param string $localized
     * @param array $data
     * @return FFAPlayForm
     *
     * Decodes the FFA play form & creates an object.
     */
    public static function decode(string $localized, array $data)
    {
        $title = TextFormat::BOLD . "Play FFA";
        $description = "Select the arena that you want to play.";
        $buttons = [
            "select.arena.template" => [
                "top.text" => "{" . FormDisplayStatistic::STATISTIC_FFA_ARENA . "}",
                "bottom.text" => "Players: {" . FormDisplayStatistic::STATISTIC_FFA_ARENA_PLAYERS . "}"
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

        return new FFAPlayForm($localized, [
            "title" => $title,
            "description" => $description,
            "buttons" => $buttons
        ]);
    }
}