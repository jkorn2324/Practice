<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-11
 * Time: 22:04
 */

namespace practice\commands\basic;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use practice\PracticeCore;
use practice\PracticeUtil;

class PingCommand extends Command
{

    public function __construct()
    {
        parent::__construct("ping", "Shows the ping of a player.", "Usage: /ping [target:player]", []);
        parent::setPermission("practice.permission.ping");
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
        if(PracticeUtil::testPermission($sender, $this->getPermission())) {
            $len = count($args);
            if($len === 0) {
                if($sender instanceof Player and PracticeCore::getPlayerHandler()->isPlayerOnline($sender->getName())) {
                    $p = PracticeCore::getPlayerHandler()->getPlayer($sender->getName());
                    $ping = $p->getPing();
                    $msg = TextFormat::GRAY . "--= Your Ping" . TextFormat::WHITE . ": $ping" . "ms" . TextFormat::GRAY . " =--";
                } else $msg = PracticeUtil::getMessage("console-usage-command");
            } elseif ($len === 1) {
                $name = $args[0];
                if(PracticeCore::getPlayerHandler()->isPlayerOnline($name)) {
                    $p = PracticeCore::getPlayerHandler()->getPlayer($name);
                    $name = $p->getPlayerName();
                    $ping = $p->getPing();
                    $msg = TextFormat::GRAY . "--= $name's Ping" . TextFormat::WHITE . ": $ping" . "ms" . TextFormat::GRAY . " =--";
                } else {
                    $msg = PracticeUtil::getMessage("not-online");
                    $msg = strval(str_replace("%player-name%", $name, $msg));
                }
            } else $msg = $this->getUsage();
        }

        if(!is_null($msg)) $sender->sendMessage($msg);
        return true;
    }
}