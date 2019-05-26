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

    public static function updateSpawnScoreboards(string $key = ""): void {

        $server = Server::getInstance();

        $playerHandler = PracticeCore::getPlayerHandler();

        $onlinePlayers = $server->getOnlinePlayers();

        $online = count($onlinePlayers);
        $max_online = $server->getMaxPlayers();
        $online_arr = ["%num%" => "$online", "%max-num%" => $max_online];

        $in_fights = $playerHandler->getPlayersInFights();;
        $in_fights_arr = ["%num%" => $in_fights];

        $in_queues = PracticeCore::getDuelHandler()->getNumberOfQueuedPlayers();
        $in_queues_arr = ["%num%" => $in_queues];

        $arr = ["online-players" => $online_arr, "in-fights" => $in_fights_arr, "in-queues" => $in_queues_arr];


        foreach ($onlinePlayers as $player) {

            if ($player->isConnected() and $playerHandler->isPlayer($player)) {
                $p = $playerHandler->getPlayer($player);
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

    public static function updateFFAScoreboards(Player $pl = null): void {

        $playerHandler = PracticeCore::getPlayerHandler();

        $players = Server::getInstance()->getOnlinePlayers();

        foreach ($players as $player) {
            if ($playerHandler->isPlayer($player)) {
                $p = $playerHandler->getPlayer($player);
                $exec = true;

                if (!is_null($pl))
                    $exec = $p->getPlayerName() !== $pl->getName();

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