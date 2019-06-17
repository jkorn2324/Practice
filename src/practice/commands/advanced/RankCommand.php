<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-20
 * Time: 20:31
 */

namespace practice\commands\advanced;


use pocketmine\command\CommandSender;
use pocketmine\Player;
use practice\commands\BaseCommand;
use practice\commands\parameters\BaseParameter;
use practice\commands\parameters\Parameter;
use practice\commands\parameters\SimpleParameter;
use practice\PracticeCore;
use practice\PracticeUtil;

class RankCommand extends BaseCommand
{

    public function __construct()
    {
        parent::__construct("rank", "The base rank command.", "/rank help");
        $parameters = [
            0 => [
                new BaseParameter("help", $this->getPermission() , "Lists all rank commands.")
            ],
            1 => [
                new BaseParameter("list", $this->getPermission(), "Lists all the server's ranks.")
            ],
            2 => [
                new BaseParameter("enable", $this->getPermission(), "Enables the ranks on the server.")
            ],
            3 => [
                new BaseParameter("disable", $this->getPermission(), "Disables the ranks on the server.")
            ],
            4 => [
                new SimpleParameter("player", Parameter::PARAMTYPE_TARGET, $this->getPermission(), "Sets the rank(s) of the player on the server."),
                new SimpleParameter("first-rank", Parameter::PARAMTYPE_STRING),
                (new SimpleParameter("second-rank", Parameter::PARAMTYPE_STRING))->setOptional(true)
            ]
        ];
        self::setParameters($parameters);
    }

    public function execute(CommandSender $sender, $commandLabel, array $args)
    {
        $msg = null;

        if(self::canExecute($sender, $args)) {
            $name = strval($args[0]);
            switch($name) {
                case "help":
                    $msg = $this->getFullUsage();
                    break;
                case "list":
                    $msg = PracticeCore::getRankHandler()->getRankList();
                    break;
                case "enable":
                    if(!PracticeUtil::isRanksEnabled()){
                        $msg = PracticeUtil::getMessage("general.rank.toggle");
                        $msg = PracticeUtil::str_replace($msg, ["%enabled%" => "enabled"]);
                    }
                    PracticeUtil::setRanksEnabled(true);
                    break;
                case "disable":
                    if(PracticeUtil::isRanksEnabled()){
                        $msg = PracticeUtil::getMessage("general.rank.toggle");
                        $msg = PracticeUtil::str_replace($msg, ["%enabled%" => "disabled"]);
                    }
                    PracticeUtil::setRanksEnabled(false);
                    break;
                default:
                    $len = count($args);
                    if($len === 3) {
                        $this->executeRanks($sender, strval($args[0]), strval($args[1]), strval($args[2]));
                    } else {
                        if($len === 2) {
                            $this->executeRanks($sender, strval($args[0]), strval($args[1]));
                        } else {
                            $msg = $this->getUsageOf($this->getParamGroupFrom($args[0]), false);
                        }
                    }
            }
        }

        if(!is_null($msg)) $sender->sendMessage($msg);
        return true;
    }

    private static function executeRanks(CommandSender $sender, string $playerName, string...$ranks) {

        $msg = null;

        if(PracticeCore::getPlayerHandler()->isPlayerOnline($playerName)) {

            $player = PracticeCore::getPlayerHandler()->getPlayer($playerName);

            $exec = false;

            if($sender instanceof Player) {
                $exec = true;
            } else {
                if($sender->getName() !== $playerName) {
                    $exec = true;
                }
            }

            if(!$exec) $msg = PracticeUtil::getMessage("console-usage-command");

            else {

                if(PracticeUtil::isRanksEnabled()){

                    $p = $player->getPlayer();

                    if(PracticeCore::getRankHandler()->areRanksValid($ranks)) {
                        $rnks = [];
                        foreach($ranks as $r) {
                            $rank = PracticeCore::getRankHandler()->getRankFromName($r);
                            $rnks[] = $rank;
                        }

                        $count = count($rnks);

                        $exec = false;

                        $name = $p->getName();

                        if($count === 2) $exec = PracticeCore::getRankHandler()->setRank($p, true, $rnks[0], $rnks[1]);
                        else $exec = PracticeCore::getRankHandler()->setRank($p, true, $rnks[0]);

                        if(!$exec){
                            $msg = PracticeUtil::getMessage("general.rank.failed");
                            $msg = strval(str_replace("%player%", $name, $msg));
                        } else {
                            if($playerName !== $sender->getName()){
                                $msg = PracticeUtil::getMessage("general.rank.change-op");
                                $msg = strval(str_replace("%player%", $name, $msg));
                            }
                        }

                        PracticeCore::getPermissionHandler()->updatePermissions($player);

                        $nameTag = PracticeUtil::getNameTagFormat($p);
                        if(!$player->isInDuel())
                            $p->setNameTag($nameTag);
                        else {
                            $duel = PracticeCore::getDuelHandler()->getDuel($p->getName());
                            if($duel->isPlayer($p))
                                $duel->setPNameTag($nameTag);
                            elseif ($duel->isOpponent($p))
                                $duel->setONameTag($nameTag);
                        }

                    } else {
                        $invalidRank = PracticeCore::getRankHandler()->getInvalidRank($ranks);
                        $msg = PracticeUtil::getMessage("general.rank.no-exist");
                        $msg = strval(str_replace("%rank%", $invalidRank, $msg));
                    }
                } else {
                    $msg = PracticeUtil::getMessage("general.rank.not-enabled");
                    $msg = strval(str_replace("%player%", $playerName, $msg));
                }
            }
        } else {
            $msg = PracticeUtil::getMessage("not-online");
            $msg = strval(str_replace("%player-name%", $playerName, $msg));
        }

        if(!is_null($msg)) $sender->sendMessage($msg);
    }

}