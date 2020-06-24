<?php

declare(strict_types=1);

namespace practice\misc;


use pocketmine\Player;

interface IDisplayText
{

    function getText(Player $player): string;
}