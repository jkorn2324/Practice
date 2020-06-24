<?php

declare(strict_types=1);

namespace practice\forms\display\types;


use pocketmine\Player;
use pocketmine\utils\TextFormat;
use practice\forms\display\FormDisplay;
use practice\forms\display\FormDisplayText;
use practice\forms\types\SimpleForm;
use practice\player\PracticePlayer;

class MenuSettingsForm extends FormDisplay
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
        foreach($buttons as $buttonLocal => $text)
        {
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
            if(!$player instanceof PracticePlayer)
            {
                return;
            }

            // TODO: Implement callable.
        });

        $form->setTitle($this->formData["title"]->getText($player));
        $form->setContent($this->formData["description"]->getText($player));
        $form->addButton($this->formData["button.settings.basic"]->getText($player));
        $form->addButton($this->formData["button.settings.builder"]->getText($player));

        $player->sendForm($form);
    }

    /**
     * @param string $localized
     * @param array $data
     * @return MenuSettingsForm|null
     *
     * Decodes the menu settings according to the class method.
     */
    public static function decode(string $localized, array $data)
    {
        $title = TextFormat::BOLD . "Settings Menu";
        if(isset($data["title"]))
        {
            $title = (string)$data["title"];
        }

        $description = "Menu for editing your game-settings.";
        if(isset($data["description"]))
        {
            $description = (string)$data["description"];
        }

        $buttons = [
            "settings.basic" => TextFormat::BLUE . TextFormat::BOLD . "Basic Settings",
            "settings.builder" => TextFormat::BLUE . TextFormat::BOLD . "Builder Settings"
        ];

        if(isset($data["buttons"]))
        {
            $buttons = array_replace($buttons, $data["buttons"]);
        }

        return new MenuSettingsForm($localized, [
            "title" => $title,
            "description" => $description,
            "buttons" => $buttons
        ]);
    }
}