<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-03
 * Time: 10:58
 */

namespace practice\commands\advanced;


use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use practice\commands\BaseCommand;
use practice\commands\parameters\BaseParameter;
use practice\commands\parameters\Parameter;
use practice\game\FormUtil;
use practice\PracticeCore;
use practice\PracticeUtil;

class ReportCommand extends BaseCommand
{

    public function __construct()
    {
        parent::__construct("report", "The base report command.", "/report help");
        $parameters = [
            0 => [
                new BaseParameter("help", Parameter::NO_PERMISSION, "Lists all the report commands.")
            ],
            1 => [
                new BaseParameter("bug", Parameter::NO_PERMISSION, "Report a bug to the staff.")
            ],
            2 => [
                new BaseParameter("player", Parameter::NO_PERMISSION, "Report a player to the staff.")
            ],
            3 => [
                new BaseParameter("staff", Parameter::NO_PERMISSION, "Report a staff member to the owner.")
            ],
            4 => [
                new BaseParameter("list", $this->getPermission(), "Lists all of the reports based on type, or in a given time span.")
            ],
            5 => [
                new BaseParameter('clear', $this->getPermission(), "Clears all the reports.")
            ]
        ];
        $this->setParameters($parameters);
    }

    public function execute(CommandSender $sender, $commandLabel, array $args) {

        $msg = null;

        if(self::canExecute($sender, $args)) {
            $cmd = strval($args[0]);
            switch($cmd) {
                case "help":
                    $msg = $this->getFullUsage();
                    break;
                case "bug":
                    $this->reportBug($sender);
                    break;
                case "player":
                    $this->reportPlayer($sender);
                    break;
                case "staff":
                    $this->reportStaff($sender);
                    break;
                case "list":
                    $this->listReports($sender);
                    break;
                case "clear":
                    $this->clearReports($sender);
                    break;
                default:
            }
        }

        if(!is_null($msg)) $sender->sendMessage($msg);

        return true;
    }


    private function reportBug(CommandSender $sender) : void {

        if(PracticeUtil::canExecBasicCommand($sender, false, true)) {
            $p = PracticeCore::getPlayerHandler()->getPlayer($sender->getName());
            $form = FormUtil::getReportBugForm();
            $p->sendForm($form);
        }
    }

    private function reportPlayer(CommandSender $sender) : void {

        if(PracticeUtil::canExecBasicCommand($sender, false, true)) {
            $onlinePlayers = count(Server::getInstance()->getOnlinePlayers());
            if($onlinePlayers > 1) {
                $p = PracticeCore::getPlayerHandler()->getPlayer($sender->getName());
                $form = FormUtil::getReportHackForm($sender->getName());
                $p->sendForm($form);
            }
        }
    }

    private function reportStaff(CommandSender $sender) : void {

        if(PracticeUtil::canExecBasicCommand($sender, false, true)) {
            $p = PracticeCore::getPlayerHandler()->getPlayer($sender->getName());
            $form = FormUtil::getReportStaffForm($sender->getName());
            $p->sendForm($form);
        }
    }

    private function listReports(CommandSender $sender) : void {

        if(PracticeUtil::canExecBasicCommand($sender, false)) {
            $p = PracticeCore::getPlayerHandler()->getPlayer($sender->getName());
            $form = FormUtil::getReportsForm();
            $p->sendForm($form);
        }
    }

    private function clearReports(CommandSender $sender) : void
    {

        if (PracticeUtil::canExecBasicCommand($sender, false)) {
            $p = PracticeCore::getPlayerHandler()->getPlayer($sender->getName());
            $msg = TextFormat::GREEN . 'Successfully cleared all reports!';
            $p->sendMessage($msg);
            PracticeCore::getReportHandler()->clearReports();
        }
    }
}