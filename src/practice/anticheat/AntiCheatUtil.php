<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-14
 * Time: 16:53
 */

declare(strict_types=1);

namespace practice\anticheat;

use practice\PracticeCore;

class AntiCheatUtil
{

    public const MAX_CHECK_FOR_PING = 50;

    public const MAX_REACH_DIST = 14.5;

    public const MAX_LETGO_REACH = 5;

    public const MAX_LETGO_CPS = 1;


    public static function canDamage($player) : bool {
        $result = false;
        if(PracticeCore::getPlayerHandler()->isPlayerOnline($player)) {
            $p = PracticeCore::getPlayerHandler()->getPlayer($player);
            if($p->canHitPlayer()) {
                $result = $p->getNoDamageTicks() <= 0;
            }
        }
        return $result;
    }
}