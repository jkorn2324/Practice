<?php

declare(strict_types=1);

namespace practice\forms\display\types;


use pocketmine\Player;
use practice\forms\display\FormDisplay;

class BasicSettingsForm extends FormDisplay
{

    /**
     * @param array $data - The input data.
     * Initializes the form data.
     */
    protected function initData(array &$data): void
    {
        // TODO: Implement initData() method.
    }

    /**
     * @param Player $player - The player we are sending the form to.
     *
     * Displays the form to the given player.
     */
    public function display(Player $player): void
    {
        // TODO: Implement display() method.
    }

    public static function decode(string $localized, array $data)
    {
        // TODO: Implement decode() method.
    }
}