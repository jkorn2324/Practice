<?php

declare(strict_types=1);

namespace practice\forms\display\types;


use pocketmine\Player;
use pocketmine\utils\TextFormat;
use practice\forms\display\FormDisplay;
use practice\forms\display\FormDisplayText;
use practice\forms\types\CustomForm;
use practice\player\info\settings\properties\BooleanSetting;
use practice\player\info\settings\SettingsInfo;
use practice\player\PracticePlayer;

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
                    $property = $settings->getProperty($localized);
                    if($property !== null)
                    {
                        $property->setValue($result);
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
            if($property instanceof BooleanSetting)
            {
                $toggleLocalized = "toggle." . $localized . "." . ($property->getValue() ? "disabled" : "enabled");
                $form->addToggle($this->formData[$toggleLocalized]->getText($player), $property->getValue(), $toggleLocalized);
            }
        }
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
        $description = "Form to edit your basic settings.";

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