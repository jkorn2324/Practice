<?php

declare(strict_types=1);

namespace practice\scoreboard\display\statistics;


use pocketmine\Player;

class DuelScoreboardStatistic extends ScoreboardStatistic
{
    public function __construct(string $localizedName, callable $callable)
    {
        parent::__construct($localizedName, $callable);
    }

    /**
     * @param Player $player
     * @return mixed
     *
     * Gets the value from a player.
     */
    public function getValue(Player $player)
    {
       // TODO:

        return "";
    }

}