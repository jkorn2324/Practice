<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-07-22
 * Time: 14:52
 */

declare(strict_types=1);

namespace jkorn\bd\forms\types;


use jkorn\bd\BasicDuelsManager;
use jkorn\bd\duels\types\BasicDuelGameType;
use jkorn\practice\forms\display\FormDisplay;
use jkorn\practice\forms\display\properties\FormDisplayText;
use jkorn\practice\forms\types\SimpleForm;
use jkorn\practice\kits\IKit;
use jkorn\practice\PracticeCore;
use pocketmine\Player;

class DuelKitSelectorForm extends FormDisplay
{

    /**
     * @param array $data - The input data.
     * Initializes the form data.
     */
    protected function initData(array &$data): void
    {
        $this->formData["title"] = new FormDisplayText($data["title"]);
        $this->formData["description"] = new FormDisplayText($data["description"]);

        $this->formData["title"] = new FormDisplayText($data["title"]);
        $this->formData["description"] = new FormDisplayText($data["description"]);

        $buttons = $data["buttons"];
        foreach ($buttons as $buttonLocal => $text) {
            $inputText = $text;
            if (is_array($text)) {
                $topLine = "";
                $bottomLine = "";
                if (isset($text["top.text"])) {
                    $topLine = $text["top.text"];
                }

                if (isset($text["bottom.text"])) {
                    $bottomLine = $text["bottom.text"];
                }

                if ($bottomLine !== "") {
                    $inputText = implode("\n", [$topLine, $bottomLine]);
                } else {
                    $inputText = $topLine;
                }
            }
            $this->formData["button.{$buttonLocal}"] = new FormDisplayText($inputText);
        }
    }

    /**
     * @param Player $player - The player we are sending the form to
     * @param mixed...$args - The arguments of the duel kit selector form.
     *
     * Displays the form to the given player.
     */
    public function display(Player $player, ...$args): void
    {
        // TODO: Implement display() method.
        /** @var BasicDuelsManager|null $duelsManager */
        $duelsManager = PracticeCore::getBaseGameManager()->getGameManager(BasicDuelsManager::NAME);
        if($duelsManager === null)
        {
            return;
        }

        // Gets the input game type.
        $gameType = $duelsManager->getGameType("1vs1");
        if(
            isset($args[0])
            && ($type = $args[0]) !== null
            && $type instanceof BasicDuelGameType)
        {
            $gameType = $type;
        }

        $form = new SimpleForm(function(Player $player, $data, $extraData)
        {
            /** @var IKit[] $kits */
            $kits = $extraData["kits"];
            if(count($kits) <= 0)
            {
                return;
            }

            if($data !== null)
            {
                $kit = $kits[(int)$data];
                /** @var BasicDuelGameType $gameType */
                $gameType = $extraData["type"];

                /** @var BasicDuelsManager|null $gameManager */
                $gameManager = PracticeCore::getBaseGameManager()->getGameManager(BasicDuelsManager::NAME);
                if($gameManager !== null)
                {
                    $awaitingManager = $gameManager->getAwaitingManager();

                    $classData = new \stdClass();
                    $classData->kit = $kit;
                    $classData->gameType = $gameType;

                    $awaitingManager->setAwaiting($player, $classData, true);
                }
            }
        });

        $form->setTitle($this->formData["title"]->getText($player));
        $form->setContent($this->formData["description"]->getText($player));

        $kits = PracticeCore::getKitManager()->getAll();
        if(count($kits) <= 0)
        {
            $button = $this->formData["button.duel.button.none"]->getText($player);
            $form->addButton($button);
            $form->setExtraData(["kits" => [], "type" => $gameType]);
            $player->sendForm($form);
            return;
        }

        $inKits = [];
        foreach($kits as $kit)
        {
            $texture = $kit->getTexture();
            $formData = $this->formData["button.duel.button.template"];
            if($texture !== "")
            {
                $form->addButton(
                    $formData->getText($player, ["type" => $gameType, "kit" => $kit]),
                    0,
                    $texture
                );
            }
            else
            {
                $form->addButton(
                    $formData->getText($player, ["type" => $gameType, "kit" => $kit])
                );
            }
            $inKits[] = $kit;
        }

        $form->setExtraData(["kits" => $inKits, "type" => $gameType]);
        $player->sendForm($form);
    }

    /**
     * @param string $localized
     * @param array $data
     * @return DuelKitSelectorForm
     *
     * Decodes the form from the data.
     */
    public static function decode(string $localized, array $data)
    {
        $title = "Select Duel Kit";
        $description = "Select the type of kit you want to play.";
        $buttons = [
            "duel.button.template" => [
                "top.text" => "{duels.basic.stat.kit}",
                "bottom.text" => "Queued: {duels.basic.stat.type.kit.awaiting}"
            ],
            "duel.button.none" => [
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
            $description = $data["description"];
        }

        if(isset($data["buttons"]))
        {
            $buttons = array_replace($buttons, $data["buttons"]);
        }

        return new DuelKitSelectorForm($localized, [
            "title" => $title,
            "description" => $description,
            "buttons" => $buttons
        ]);
    }
}