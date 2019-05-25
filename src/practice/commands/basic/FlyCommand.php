<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-20
 * Time: 17:16
 */

declare(strict_types=1);

namespace practice\commands\basic;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use practice\PracticeCore;
use practice\PracticeUtil;

class FlyCommand extends Command
{

    public function __construct()
    {
        parent::__construct("fly", "Allows a player to fly.", "Usage: /fly", []);
        parent::setPermission("practice.permission.fly");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     *
     * @return mixed
     * @throws CommandException
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        $msg = null;

        if(PracticeUtil::canExecBasicCommand($sender, false)) {
            if(PracticeUtil::testPermission($sender, $this->getPermission())){
                $len = count($args);
                if($len === 0) {

                    $p = PracticeCore::getPlayerHandler()->getPlayer($sender->getName());
                    $isNowFlying = false;
                    $player = $p->getPlayer();

                    if(PracticeUtil::canFly($player)) {
                        PracticeUtil::setCanFly($player, false);
                    } else {
                        $isNowFlying = true;
                        PracticeUtil::setCanFly($player, true);
                    }

                    $replaced = ($isNowFlying ? "fly" : "land");
                    $msg = PracticeUtil::getMessage("general.fly.success-direct-$replaced");

                } else {
                    $msg = $this->getUsage();
                }
            }
        }

        if(!is_null($msg)) $sender->sendMessage($msg);
        return true;
    }
}