<?php

declare(strict_types=1);

namespace jkorn\practice\forms\display\types;


use jkorn\practice\forms\display\properties\FormDisplayText;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use jkorn\practice\forms\display\FormDisplay;
use jkorn\practice\forms\types\CustomForm;
use jkorn\practice\player\info\settings\properties\BooleanSettingProperty;
use jkorn\practice\player\info\settings\SettingsInfo;
use jkorn\practice\player\PracticePlayer;

class BasicSettingsForm extends FormDisplay
{
    /**
     * @param array $data - The input data.
     * Initializes the form data.
     */
    protected function initData(array &$data): void
    {
        $this->formData["title"] = new FormDisplayText($data["title"]);
        $this->formData["description"] = new FormDisplayText($data["description"]);

        $toggles = $data["toggles"];
        foreach ($toggles as $key => $data) {
            foreach ($data as $type => $value) {
                $inputKey = "toggle.{$key}.{$type}";
                $this->formData[$inputKey] = new FormDisplayText($value);
            }
        }
        // var_dump(array_keys($this->formData));
    }

    /**
     * @param Player $player - The player we are sending the form to.
     *
     * Displays the form to the given player.
     */
    public function display(Player $player): void
    {
        if (!$player instanceof PracticePlayer) {
            return;
        }

        $form = new CustomForm(function (Player $player, $data, $extraData) {

            if (!$player instanceof PracticePlayer) {
                return;
            }

            if ($data !== null)
            {
                $settings = $player->getSettingsInfo();
                foreach($data as $localized => $result)
                {
                    if(!is_string($localized))
                    {
                        continue;
                    }

                    $property = $settings->getProperty($localized);
                    if($property !== null && $property->setValue($result))
                    {
                        // Literally updates the values.
                        switch($localized)
                        {
                            case SettingsInfo::SCOREBOARD_DISPLAY:
                                $player->settingsUpdateScoreboard();
                                break;
                        }
                    }
                }
            }
        });

        $settingsInfo = $player->getSettingsInfo();

        $form->setTitle($this->formData["title"]->getText($player));
        $form->addLabel($this->formData["description"]->getText($player));

        $properties = $settingsInfo->getProperties();
        foreach($properties as $localized => $property)
        {
            if($property instanceof BooleanSettingProperty)
            {
                $toggleLocalized = "toggle." . $localized . "." . ($property->getValue() ? "disabled" : "enabled");
                if(isset($this->formData[$toggleLocalized]))
                {
                    $text = $this->formData[$toggleLocalized];
                    $form->addToggle($text->getText($player), $property->getValue(), $localized);
                }
            }
        }

        $player->sendForm($form);
    }

    /**
     * @param string $localized
     * @param array $data
     * @return BasicSettingsForm
     *
     * Decodes the settings form based on the data.
     */
    public static function decode(string $localized, array $data)
    {
        // TODO: Edit so it corresponds with the SettingsInfo class
        $title = TextFormat::BOLD . "Basic Settings";
        $description = "Form to edit your duels settings.";

        $toggles = SettingsInfo::getSettingsFormDisplay();

        if (isset($data["title"])) {
            $title = (string)$data["title"];
        }

        if (isset($data["description"])) {
            $description = (string)$data["description"];
        }

        if (isset($data["toggles"])) {
            $toggles = array_replace($toggles, $data["toggles"]);
        }

        return new BasicSettingsForm($localized, [
            "title" => $title,
            "description" => $description,
            "toggles" => $toggles
        ]);
    }
}