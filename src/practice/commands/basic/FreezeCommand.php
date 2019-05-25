<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-20
 * Time: 17:24
 */

declare(strict_types=1);

namespace practice\commands\basic;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use practice\PracticeCore;
use practice\PracticeUtil;

class FreezeCommand extends Command
{

    private $freeze = false;

    public function __construct(bool $freeze = true)
    {
        parent::__construct(($freeze ? "freeze" : "unfreeze"), ($freeze ? "Freeze" : "Unfreeze") . " a player.", "Usage: /" . ($freeze ? "freeze" : "unfreeze") . " [target:player]", []);
        parent::setPermission("practice.permission.freeze");
        $this->freeze = $freeze;
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
            if(PracticeUtil::testPermission($sender, $this->getPermission())){
                $len = count($args);
                if($len === 1) {
                    $name = strval($args[0]);

                    if(PracticeCore::getPlayerHandler()->isPlayerOnline($name)) {

                        $p = PracticeCore::getPlayerHandler()->getPlayer($name);

                        $player = $p->getPlayer();

                        $playerMessage = null;

                        if($this->freeze) {
                            PracticeUtil::setFrozen($player, true);
                            if($player->getName() === $sender->getName()){
                                $msg = PracticeUtil::getMessage("general.frozen.active-direct");
                            } else {
                                $msg = strval(str_replace("%player%", $player->getName(), PracticeUtil::getMessage("general.frozen.active-op")));
                                $playerMessage = PracticeUtil::getMessage("general.frozen.active-direct");
                            }
                        } else {
                            PracticeUtil::setFrozen($player, false);
                            if($player->getName() === $sender->getName()) {
                                $msg = PracticeUtil::getMessage("general.frozen.inactive-direct");
                            } else {
                                $msg = strval(str_replace("%player%", $player->getName(), PracticeUtil::getMessage("general.frozen.inactive-op")));
                                $playerMessage = PracticeUtil::getMessage("general.frozen.inactive-direct");
                            }
                        }

                        if(!is_null($playerMessage)) $player->sendMessage($playerMessage);

                    } else {
                        $msg = PracticeUtil::getMessage("not-online");
                        $msg = strval(str_replace("%player-name%", $name, $msg));
                    }
                } else {
                    $msg = $this->getUsage();
                }
            }
        }

        if(!is_null($msg)) $sender->sendMessage($msg);
        return true;
    }
}