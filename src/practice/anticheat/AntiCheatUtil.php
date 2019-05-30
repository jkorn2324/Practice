<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-14
 * Time: 16:53
 */

declare(strict_types=1);

namespace practice\anticheat;

use pocketmine\Player;
use pocketmine\Server;
use practice\PracticeCore;

class AntiCheatUtil
{

    public const MAX_CHECK_FOR_PING = 50;

    public const MAX_REACH_DIST = 14.5;

    public const MAX_LETGO_REACH = 5;

    public const MAX_LETGO_CPS = 1;

    private const MAX_PING = 200;
    private const BLOCKS_AIR_LIMIT = 6.6;


    public static function canDamage($player) : bool {
        $result = false;
        if(PracticeCore::getPlayerHandler()->isPlayerOnline($player)) {
            $p = PracticeCore::getPlayerHandler()->getPlayer($player);
            if($p->canHitPlayer())
                $result = $p->getNoDamageTicks() <= 0;
        }
        return $result;
    }

    /**
     * @param Player $entity
     * @param Player $damager
     */
    public static function checkForReach(Player $entity, Player $damager): void {
        if(!self::checkPing($damager) or $damager->getGamemode() === Player::CREATIVE) {
            return;
        }

        $distance = $damager->distance($entity);
        if($distance > self::BLOCKS_AIR_LIMIT) {
            self::sendReachLog($damager, $distance);
        }
    }

    /**
     * @param Player $player
     * @return bool
     */
    private static function checkPing(Player $player): bool {
        return $player->getPing() < self::MAX_PING;
    }

    /**
     * @param Player $player
     * @param float $distance
     */
    private static function sendReachLog(Player $player, float $distance): void {
        self::sendLog(
            "§7{$player->getName()} might be reaching! Distance: \n" . "§c" .
            round($distance, 1) .  " §7(" . $player->getPing() . " ms)"
        );
    }

    /**
     * @param string $message
     */
    private static function sendLog(string $message): void {

        foreach(Server::getInstance()->getOnlinePlayers() as $player) {
            if(!PracticeCore::getPlayerHandler()->isStaffMember($player->getName())) {
                continue;
            }

            $player->sendTip("§8[§eAntiCheat§8] " . $message);
        }
    }

}