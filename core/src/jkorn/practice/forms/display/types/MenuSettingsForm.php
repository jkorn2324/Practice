<?php

declare(strict_types=1);

namespace jkorn\practice\forms\display\types;


use jkorn\practice\forms\display\ButtonDisplayText;
use jkorn\practice\forms\display\FormDisplayText;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use jkorn\practice\forms\display\FormDisplay;
use jkorn\practice\forms\display\manager\PracticeFormManager;
use jkorn\practice\forms\types\SimpleForm;
use jkorn\practice\player\PracticePlayer;
use jkorn\practice\PracticeCore;

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
        foreach ($buttons as $buttonLocal => $text) {

            $formData = ButtonDisplayText::decode($text);
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
        $form = new SimpleForm(function (Player $player, $data, $extraData) {

            if (!$player instanceof PracticePlayer) {
                return;
            }

            if ($data !== null) {

                switch ((int)$data) {
                    case 0:
                        $form = PracticeCore::getBaseFormDisplayManager()->getForm(PracticeFormManager::FORM_SETTINGS_BASIC);
                        break;
                    case 1:
                        // TODO: Check for builder mode permissions first.
                        $form = PracticeCore::getBaseFormDisplayManager()->getForm(PracticeFormManager::FORM_SETTINGS_BUILDER_MODE);
                        break;
                }

                if (isset($form) && $form instanceof FormDisplay) {
                    $form->display($player);
                } else {
                    // TODO: Send message.
                }
            }
            // TODO: Implement callable.
        });

        $form->setTitle($this->formData["title"]->getText($player));
        $form->setContent($this->formData["description"]->getText($player));

        $form->addButton($this->formData["button.settings.basic"]->getText($player, null, false));
        $form->addButton($this->formData["button.settings.builder"]->getText($player, null, false));

        $player->sendForm($form);
    }

    /**
     * @param string $localized
     * @param array $data
     * @return MenuSettingsForm
     *
     * Decodes the menu settings according to the class method.
     */
    public static function decode(string $localized, array $data)
    {
        $title = TextFormat::BOLD . "Settings Menu";
        if (isset($data["title"])) {
            $title = (string)$data["title"];
        }

        $description = "Menu for editing your game-settings.";
        if (isset($data["description"])) {
            $description = (string)$data["description"];
        }

        $buttons = [
            "settings.basic" => TextFormat::BLUE . TextFormat::BOLD . "Basic Settings",
            "settings.builder" => TextFormat::BLUE . TextFormat::BOLD . "Builder Settings"
        ];

        if (isset($data["buttons"])) {
            $buttons = array_replace($buttons, $data["buttons"]);
        }

        return new MenuSettingsForm($localized, [
            "title" => $title,
            "description" => $description,
            "buttons" => $buttons
        ]);
    }
}