<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-06-07
 * Time: 12:47
 */

declare(strict_types=1);

namespace practice\commands\basic;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use practice\PracticeCore;
use practice\PracticeUtil;

class PlayerInfoCommand extends Command
{

    public function __construct() {
        parent::__construct('p-info', 'Display the device info of a player.', 'Usage: /p-info [target:player]', ['plinfo', 'info']);
        parent::setPermission('practice.permission.pinfo');
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

        $len = count($args);

        if(PracticeUtil::canExecBasicCommand($sender, $len > 0)) {

            if(PracticeUtil::testPermission($sender, $this->getPermission())){

                if($len <= 1) {

                    $name = ($len === 1) ? strval($args[0]) : $sender->getName();

                    $playerHandler = PracticeCore::getPlayerHandler();

                    if($playerHandler->isPlayerOnline($name)) {

                        $p = $playerHandler->getPlayer($name);

                        $info = $p->getDeviceInfo();

                        foreach($info as $message)
                            $sender->sendMessage($message);

                    } else {
                        $msg = PracticeUtil::getMessage("not-online");
                        $msg = strval(str_replace("%player-name%", $name, $msg));
                    }
                } else $msg = $this->getUsage();
            }
        }

        if(!is_null($msg)) $sender->sendMessage($msg);
        return true;
    }
}