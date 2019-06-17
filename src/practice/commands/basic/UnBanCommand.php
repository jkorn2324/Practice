<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-06-06
 * Time: 12:03
 */

declare(strict_types=1);

namespace practice\commands\basic;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use practice\game\FormUtil;
use practice\PracticeCore;
use practice\PracticeUtil;

class UnBanCommand extends Command
{

    public function __construct()
    {
        parent::__construct('unban', 'Ban a player permanently or temporarily.', 'Usage: /ban [target:player] [permanently:true|false]', []);
        parent::setPermission('pocketmine.command.unban.player');
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

        if(PracticeUtil::canExecBasicCommand($sender) and PracticeUtil::testPermission($sender, $this->getPermission())) {

            $count = count($args);
            $sendUsage = true;

            if($count === 1) {

                $player = strval($args[0]);

                $sendUsage = false;

                //TODO CHECK IF PLAYER IS BANNED AND IF SO UNBAN PLAYER
                    /*$form = FormUtil::getBanForm($player, $bool);
                    $p->sendForm($form, ['permanently' => $bool]);*/
            }

            if($sendUsage === true) $msg = $this->getUsage();
        }

        if(!is_null($msg)) $sender->sendMessage($msg);

        return true;
    }
}