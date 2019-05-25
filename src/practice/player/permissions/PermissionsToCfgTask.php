<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-23
 * Time: 15:27
 */

declare(strict_types=1);

namespace practice\player\permissions;


use pocketmine\scheduler\Task;
use practice\PracticeCore;

class PermissionsToCfgTask extends Task
{

    public function __construct() {}

    /**
     * Actions to execute when run
     *
     * @param int $currentTick
     *
     * @return void
     */
    public function onRun(int $currentTick) {
        PracticeCore::getPermissionHandler()->initPermissions();
    }
}