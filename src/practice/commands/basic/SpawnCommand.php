<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-20
 * Time: 22:38
 */

namespace practice\commands\basic;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use practice\PracticeCore;
use practice\PracticeUtil;

class SpawnCommand extends Command
{

    public function __construct()
    {
        parent::__construct("spawn", "Teleports player back to the lobby/spawn.", "Usage: /spawn", ["hub"]);
        parent::setPermission("practice.permission.spawn");
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
        if(PracticeUtil::canExecBasicCommand($sender, false, true)) {
            if(PracticeCore::getPlayerHandler()->isPlayerOnline($sender->getName())) {
                $player = PracticeCore::getPlayerHandler()->getPlayer($sender->getName());
                $count = count($args);
                if($count === 0) {

                    $exec = true;

                    if($player->isInArena() and !$player->isInDuel()) {
                        if($player->isInCombat()) {
                            $exec = false;
                        }
                    }

                    if($exec){

                        if(PracticeCore::getDuelHandler()->isASpectator($player->getPlayerName())) {
                            $duel = PracticeCore::getDuelHandler()->getDuelFromSpec($player->getPlayerName());
                            $duel->removeSpectator($player->getPlayerName(), true);
                        }

                        $msg = PracticeUtil::getMessage("spawn-message");
                        PracticeUtil::resetPlayer($player->getPlayer(), true);
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