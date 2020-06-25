<?php

declare(strict_types=1);

namespace practice\forms\display\types;


use pocketmine\Player;
use pocketmine\utils\TextFormat;
use practice\forms\display\FormDisplay;
use practice\forms\display\FormDisplayText;
use practice\forms\types\CustomForm;
use practice\player\PracticePlayer;

class BasicSettingsForm extends FormDisplay
{

    const PE_ONLY_QUEUES = "queues.peOnly";
    const PLAYER_DISGUISE = "player.disguise";
    const SCOREBOARD_DISPLAY = "scoreboard.display";
    const TAP_ITEM = "tap.item";

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

            if ($data !== null) {
                // TODO: Toggles.
            }
        });

        $settingsInfo = $player->getSettingsInfo();

        $form->setTitle($this->formData["title"]->getText($player));
        $form->addLabel($this->formData["description"]->getText($player));

        $peOnlyToggle = "toggle." . self::PE_ONLY_QUEUES . "." . ($settingsInfo->isPeOnly() ? "disabled" : "enabled");
        $form->addToggle($this->formData[$peOnlyToggle]->getText($player));

        $disguiseToggle = "toggle." . self::PLAYER_DISGUISE . "." . ($player->isDisguised() ? "disabled" : "enabled");
        $form->addToggle($this->formData[$disguiseToggle]->getText($player));

        $scoreboardToggle = "toggle." . self::SCOREBOARD_DISPLAY . "." . ($settingsInfo->isScoreboardEnabled() ? "disabled" : "enabled");
        $form->addToggle($this->formData[$scoreboardToggle]->getText($player));

        $tapItemToggle = "toggle." . self::TAP_ITEM . "." . ($settingsInfo->isTapItemsEnabled() ? "disabled" : "enabled");
        $form->addToggle($this->formData[$tapItemToggle]->getText($player));
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
        $title = TextFormat::BOLD . "Basic Settings";
        $description = "Form to edit your basic settings.";
        $toggles = [
            self::PE_ONLY_QUEUES => [
                "enabled" => "Enable PE Only Queues",
                "disabled" => "Disable PE Only Queues"
            ],
            self::PLAYER_DISGUISE => [
                "enabled" => "Enable Disguise",
                "disabled" => "Disable Disguise"
            ],
            self::SCOREBOARD_DISPLAY => [
                "enabled" => "Enable Scoreboard",
                "disabled" => "Disable Scoreboard"
            ],
            self::TAP_ITEM => [
                "enabled" => "Enable Tap-Item",
                "disabled" => "Disable Tap-Item"
            ]
        ];

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