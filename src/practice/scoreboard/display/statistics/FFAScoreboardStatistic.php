<?php

declare(strict_types=1);

namespace practice\scoreboard\display\statistics;


use pocketmine\Player;
use practice\player\PracticePlayer;

class FFAScoreboardStatistic extends ScoreboardStatistic
{

    /**
     * @param Player $player
     * @return mixed
     *
     * Gets the value from the player.
     */
    public function getValue(Player $player)
    {
        $callable = $this->callable;
        if(!$player instanceof PracticePlayer || !$player->isInFFA())
        {
            return $callable($player, $this->server, null);
        }

        return $callable($player, $this->server, $player->getFFAArena());
    }
}