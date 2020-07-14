<?php

declare(strict_types=1);

namespace practice\forms\display\types;


use pocketmine\Player;
use pocketmine\utils\TextFormat;
use practice\forms\display\FormDisplay;
use practice\forms\display\FormDisplayText;
use practice\forms\types\CustomForm;

class BuilderSettingsForm extends FormDisplay
{

    /**
     * @param array $data - The input data.
     * Initializes the form data.
     */
    protected function initData(array &$data): void
    {
        $this->formData["title"] = new FormDisplayText($data["title"]);
        $this->formData["description"] = new FormDisplayText($data["description"]);

        if(isset($data["toggles"]))
        {
            $toggles = $data["toggles"];
            foreach($toggles as $key => $data)
            {
                foreach($data as $type => $value)
                {
                    $inputKey = "toggle.{$key}.{$type}";
                    $this->formData[$inputKey] = new FormDisplayText((string)$value);
                }
            }
        }

        if(isset($data["labels"]))
        {
            $labels = $data["labels"];
            foreach($labels as $key => $label)
            {
                $this->formData["label.{$key}"] = new FormDisplayText((string)$label);
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
        // TODO: Implement display() method.
        $form = new CustomForm(function(Player $player, $data, $extraData) {

        });

        $form->setTitle($this->formData["title"]->getText($player));
        $form->addLabel($this->formData["description"]->getText($player));

        $player->sendForm($form);
        // TODO: Builder mode toggle.
    }

    /**
     * @param string $localized
     * @param array $data
     * @return BuilderSettingsForm
     *
     * Decodes the builder settings form based on data.
     */
    public static function decode(string $localized, array $data)
    {
        // TODO: Edit decoded information.

        $title = TextFormat::BOLD . "Builder Mode Settings";
        $description = "Form to edit builder mode settings";
        $toggles = [
            "builder.mode" => [
                "enabled" => "Enable Builder Mode",
                "disabled" => "Disable Builder Mode"
            ]
        ];
        $labels = [
            "enabled.worlds" => "Select the worlds that you want to enable/disable builder mode for:"
        ];

        if(isset($data["title"]))
        {
            $title = (string)$data["title"];
        }

        if(isset($data["description"]))
        {
            $description = (string)$data["description"];
        }

        if(isset($data["toggles"]))
        {
            $toggles = array_replace($toggles, $data["toggles"]);
        }

        if(isset($data["labels"]))
        {
            $labels = array_replace($labels, $data["labels"]);
        }

        return new BuilderSettingsForm($localized, [
            "title" => $title,
            "description" => $description,
            "toggles" => $toggles,
            "labels" => $labels
        ]);
    }
}