<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-20
 * Time: 18:23
 */

namespace practice\commands\basic;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\Player;
use pocketmine\Server;
use practice\PracticeUtil;

class KickAllCommand extends Command
{

    public function __construct()
    {
        parent::__construct("kick-all", "Kicks everyone from the server.", "Usage: /kick-all", []);
        parent::setPermission("practice.permission.kickall");
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
        if(PracticeUtil::canExecBasicCommand($sender)) {
            if(PracticeUtil::testPermission($sender, $this->getPermission())) {
                foreach(Server::getInstance()->getOnlinePlayers() as $player) {
                    $exec = true;
                    if($sender instanceof Player) {
                        if($player->getName() === $sender->getName()) {
                            $exec = false;
                        }
                    }

                    if($exec) {
                        $player->kick(PracticeUtil::getMessage("kick-all-message"));
                    }
                }
            }
        }

        if(!is_null($msg)) $sender->sendMessage($msg);
        return true;
    }
}