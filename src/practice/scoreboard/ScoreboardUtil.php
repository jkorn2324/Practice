<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-10
 * Time: 16:18
 */

declare(strict_types=1);

namespace practice\scoreboard;

use pocketmine\Player;
use pocketmine\Server;
use practice\PracticeCore;

class ScoreboardUtil
{

    public static function updateSpawnScoreboards(string $key = ""): void
    {

        $online = count(Server::getInstance()->getOnlinePlayers());
        $max_online = Server::getInstance()->getMaxPlayers();
        $online_arr = ["%num%" => "$online", "%max-num%" => $max_online];

        $in_fights = PracticeCore::getPlayerHandler()->getPlayersInFights();;
        $in_fights_arr = ["%num%" => $in_fights];

        $in_queues = PracticeCore::getDuelHandler()->getNumberOfQueuedPlayers();
        $in_queues_arr = ["%num%" => $in_queues];

        $arr = ["online-players" => $online_arr, "in-fights" => $in_fights_arr, "in-queues" => $in_queues_arr];


        foreach (Server::getInstance()->getOnlinePlayers() as $player) {

            if ($player->isConnected() and PracticeCore::getPlayerHandler()->isPlayer($player)) {
                $p = PracticeCore::getPlayerHandler()->getPlayer($player);
                $scoreboard = $p->getCurrentScoreboard();

                if ($scoreboard === Scoreboard::SPAWN_SCOREBOARD) {

                    if ($key === "") $p->updateScoreboard();

                    else {
                        if (isset($arr[$key]) and count($arr[$key]) > 0) $p->updateScoreboard($key, $arr[$key]);
                    }
                }
            }
        }
    }

    public static function updateFFAScoreboards(Player $pl = null): void
    {

        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            if (PracticeCore::getPlayerHandler()->isPlayer($player)) {
                $p = PracticeCore::getPlayerHandler()->getPlayer($player);
                $exec = true;

                if (!is_null($pl)) {
                    if ($p->getPlayerName() === $pl->getName()) {
                        $exec = false;
                    }
                }

                if ($exec === true) {
                    $scoreboard = $p->getCurrentScoreboard();
                    if ($scoreboard === Scoreboard::FFA_SCOREBOARD) {
                        $p->updateScoreboard();
                    }
                }
            }
        }
    }
}