<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-06-06
 * Time: 10:31
 */

declare(strict_types=1);

namespace practice\commands\basic;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use practice\game\FormUtil;
use practice\PracticeCore;
use practice\PracticeUtil;

class BanCommand extends Command
{

    public function __construct()
    {
        parent::__construct('ban', 'Ban a player permanently or temporarily.', 'Usage: /unban [target:player]', []);
        parent::setPermission("pocketmine.command.ban.player");
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
        if(PracticeUtil::canExecBasicCommand($sender) and PracticeUtil::testPermission($sender, $this->getPermission())) {

            $p = PracticeCore::getPlayerHandler()->getPlayer($sender->getName());

            $count = count($args);
            $sendUsage = true;

            if($count === 2) {

                $player = strval($args[0]);
                $bool = $args[1];

                if(is_bool($bool)){
                    $sendUsage = false;
                    $form = FormUtil::getBanForm($player, $bool);
                    $p->sendForm($form, ['permanently' => $bool]);
                }
            }

            if($sendUsage === true) $msg = $this->getUsage();
        }

        if(!is_null($msg)) $sender->sendMessage($msg);

        return true;
    }
}