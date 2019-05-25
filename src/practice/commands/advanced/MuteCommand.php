<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-20
 * Time: 18:56
 */

declare(strict_types=1);

namespace practice\commands\advanced;


use pocketmine\command\CommandSender;
use pocketmine\Player;
use practice\commands\BaseCommand;
use practice\commands\parameters\BaseParameter;
use practice\commands\parameters\Parameter;
use practice\commands\parameters\SimpleParameter;
use practice\PracticeCore;
use practice\PracticeUtil;

class MuteCommand extends BaseCommand
{

    public function __construct()
    {
        parent::__construct("mute", "The base mute command.", "/mute help");

        $parameters = [
            0 => [
                new BaseParameter("help", $this->getPermission(), "Displays all mute commands."),
            ],
            1 => [
                new BaseParameter("server", $this->getPermission(), "Mutes/unmutes all players on the server."),
                new SimpleParameter("mute", Parameter::PARAMTYPE_BOOLEAN)
            ],
            2 => [
                new SimpleParameter("player", Parameter::PARAMTYPE_TARGET, $this->getPermission(), "Mutes/unmutes a player on the server."),
                new SimpleParameter("mute", Parameter::PARAMTYPE_BOOLEAN)
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
                case "server":
                    $mute = $this->getBoolean($args[1]);
                    PracticeCore::getInstance()->setServerMuted($mute);
                    $str = ($mute ? "enabled" : "disabled");
                    PracticeUtil::broadcastMsg(PracticeUtil::getMessage("general.mute.sm-$str"));
                    if(!($sender instanceof Player)) $msg = PracticeUtil::getMessage("general.mute.sm-$str");
                    break;
                default:
                    $mute = $this->getBoolean($args[1]);
                    self::mutePlayer($sender, $name, $mute);
            }
        }
        if(!is_null($msg)) $sender->sendMessage($msg);
        return true;
    }

    private static function mutePlayer(CommandSender $sender, string $playerName, bool $mute) : void {
        $msg = null;

        if(PracticeCore::getPlayerHandler()->isPlayerOnline($playerName)) {
            $p = PracticeCore::getPlayerHandler()->getPlayer($playerName);

            $playerMsg = null;

            if(PracticeCore::getPlayerHandler()->isPlayerMuted($playerName)) {

                if($mute) {
                    $str = ($sender->getName() === $playerName ? "You" : $playerName);
                    $msg = PracticeUtil::getMessage("general.mute.already-muted");
                    $msg = strval(str_replace("%player%", $str, $msg));
                } else {
                    PracticeCore::getPlayerHandler()->unmutePlayer($playerName);
                    if($sender->getName() === $playerName) {
                        $msg = PracticeUtil::getMessage("general.mute.not-muted-personal");
                    } else {
                        $msg = PracticeUtil::getMessage("general.mute.not-muted-op");
                        $msg = strval(str_replace("%player%", $playerName, $msg));
                        $playerMsg = PracticeUtil::getMessage("general.mute.not-muted-personal");
                    }
                }
            } else {

                if($mute) {
                    PracticeCore::getPlayerHandler()->mutePlayer($playerName);
                    if($sender->getName() === $playerName) {
                        $msg = PracticeUtil::getMessage("general.mute.mute-personal");
                    } else {
                        $msg = PracticeUtil::getMessage("general.mute.mute-op");
                        $msg = strval(str_replace("%player%", $playerName, $msg));
                        $playerMsg = PracticeUtil::getMessage("general.mute.mute-personal");
                    }
                } else {
                    $str = ($sender->getName() === $playerName ? "You" : $playerName);
                    $msg = PracticeUtil::getMessage("general.mute.already-unmuted");
                    $msg = strval(str_replace("%player%", $str, $msg));
                }
            }

            if(!is_null($playerMsg)) $p->sendMessage($playerMsg);
        } else {
            $msg = PracticeUtil::getMessage("not-online");
            $msg = strval(str_replace("%player-name%", $playerName, $msg));
        }

        if(!is_null($msg)) $sender->sendMessage($msg);
    }

}