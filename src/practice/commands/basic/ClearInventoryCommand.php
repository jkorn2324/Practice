<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-20
 * Time: 16:55
 */

declare(strict_types=1);

namespace practice\commands\basic;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use practice\PracticeCore;
use practice\PracticeUtil;

class ClearInventoryCommand extends Command
{

    public function __construct()
    {
        parent::__construct("clear-inv", "Clears the inventory of the player that performs the command.", "Usage: /clear-inv", []);
        parent::setPermission("practice.permission.clear-inv");
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
                $player = PracticeCore::getPlayerHandler()->getPlayer($sender->getName());
                $len = count($args);
                if($len === 0){
                    $player->getPlayer()->getInventory()->clearAll();
                    $player->getPlayer()->getArmorInventory()->clearAll();
                    $msg = PracticeUtil::getMessage("general.clear-inv.success-direct");
                } else {
                    $msg = $this->getUsage();
                }
            }
        }

        if(!is_null($msg)) $sender->sendMessage($msg);

        return true;
    }
}