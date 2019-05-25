<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-20
 * Time: 16:33
 */

declare(strict_types=1);

namespace practice\commands\basic;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\Player;
use practice\duels\groups\Request;
use practice\PracticeCore;
use practice\PracticeUtil;

class AcceptCommand extends Command
{

    public function __construct()
    {
        parent::__construct("accept", "Allows player to accept a duel request.", "Usage: /accept [target:player]");
        self::setPermission("practice.permission.accept");
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

        if ($sender instanceof Player) {
            if (PracticeUtil::canExecAcceptCommand($sender, $this->getPermission())) {
                $p = PracticeCore::getPlayerHandler()->getPlayer($sender->getPlayer());
                $count = count($args);
                if ($count === 1) {
                    if (PracticeUtil::canAcceptPlayer($sender->getPlayer(), strval($args[0])))
                        PracticeCore::get1vs1Handler()->acceptRequest($sender->getPlayer(), strval($args[0]));
                } else $msg = $this->getUsage();
            }
        } else $msg = PracticeUtil::getMessage("console-usage-command");

        if (!is_null($msg)) $sender->sendMessage($msg);

        return true;
    }
}