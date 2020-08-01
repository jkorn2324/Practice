<?php

declare(strict_types=1);

namespace jkorn\practice\forms\display\types;


use jkorn\practice\forms\display\FormDisplay;
use pocketmine\Player;

class SpectatorJoinForm extends FormDisplay
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
     * @param string $localized
     * @param array $data
     *
     * @return SpectatorJoinForm
     *
     * Decodes the Spectator Join form based on localized name & data.
     *
     */
    public static function decode(string $localized, array $data)
    {
        return null;
    }

    /**
     * @param Player $player
     * @param mixed ...$args
     *
     * Displays the form to the player.
     */
    public function display(Player $player, ...$args): void
    {
        // TODO: Implement display() method.
    }
}