<?php

declare(strict_types=1);

namespace jkorn\practice\misc;


use pocketmine\Player;

interface IDisplayText
{

    /**
     * @param Player $player
     * @param mixed|null $args
     * @return string
     *
     * Gets the text from the player and its arguments.
     */
    public function getText(Player $player, $args = null): string;
}