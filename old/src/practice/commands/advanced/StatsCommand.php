<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-11
 * Time: 16:20
 */

declare(strict_types=1);

namespace old\practice\commands\advanced;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use old\practice\commands\BaseCommand;
use old\practice\commands\parameters\BaseParameter;
use old\practice\commands\parameters\Parameter;
use old\practice\commands\parameters\SimpleParameter;
use old\practice\game\FormUtil;
use old\practice\player\PracticePlayer;
use old\practice\PracticeCore;
use old\practice\PracticeUtil;
use old\practice\scoreboard\ScoreboardUtil;

class StatsCommand extends BaseCommand
{

    public function __construct()
    {
        parent::__construct("stats", "The base stats command.", "/stats help");
        $parameters = [
            0 => [
                new BaseParameter("help", Parameter::NO_PERMISSION, "Displays all of the stats commands.")
            ],
            1 => [
                new BaseParameter("me", Parameter::NO_PERMISSION, "Displays your stats.")
            ],
            2 => [
                new SimpleParameter("player-name", Parameter::PARAMTYPE_TARGET, Parameter::NO_PERMISSION, "Displays the stats of another player.")
            ],
            3 => [
                new BaseParameter("reset", $this->getPermission(), "Resets the server stats.")
            ]
        ];
        $this->setParameters($parameters);
    }

    /**
     * @param CommandSender $sender
     * @param $commandLabel
     * @param array $args
     * @return bool|mixed
     */
    public function execute(CommandSender $sender, $commandLabel, array $args) {

        $msg = null;

        if($this->canExecute($sender, $args)) {

            $name = strval($args[0]);

            switch($name) {
                case "help":
                    $msg = $this->getFullUsage();
                    break;
                case "me":
                    $this->getStats($sender);
                    break;
                case "reset":
                    $this->resetStats($sender);
                    break;
                default:
                    $this->getStats($sender, $name);
            }
        }

        if(!is_null($msg)) $sender->sendMessage($msg);

        return true;
    }

    private function getStats(CommandSender $sender, string $player = null) : void {

        $msg = null;

        $statsOf = null;

        if($player === null) {
            if($sender instanceof Player) {
                $statsOf = PracticeCore::getPlayerHandler()->getPlayer($sender->getName());
            } else $msg = PracticeUtil::getMessage("console-usage-command");
        } else {
            if(PracticeCore::getPlayerHandler()->isPlayerOnline($player)) {
                $statsOf = PracticeCore::getPlayerHandler()->getPlayer($player);
            } else {
                $msg = PracticeUtil::getMessage("not-online");
                $msg = strval(str_replace("%player-name%", $player, $msg));
            }
        }

        if(!is_null($statsOf) and $statsOf instanceof PracticePlayer){
            if(PracticeUtil::canExecBasicCommand($sender, true, true)) {
                $msg = "";
                $arr = PracticeCore::getPlayerHandler()->getStats($statsOf->getPlayerName(), false);
                $keys = array_keys($arr);
                foreach($keys as $key) {
                    $value = strval($arr[$key]) . "\n";
                    $msg .= $value;
                }
            }
        }

        if(!is_null($msg)) $sender->sendMessage($msg);
    }

    private function resetStats(CommandSender $sender) : void {

        if(PracticeUtil::canExecBasicCommand($sender, true))
            PracticeCore::getPlayerHandler()->resetStats();


        //ScoreboardUtil::updateFFAScoreboards();
    }
}