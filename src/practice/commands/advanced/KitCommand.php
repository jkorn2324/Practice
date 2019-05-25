<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-13
 * Time: 19:13
 */

namespace practice\commands\advanced;

use pocketmine\command\CommandSender;
use pocketmine\entity\Effect;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use practice\commands\BaseCommand;
use practice\commands\parameters\BaseParameter;
use practice\commands\parameters\Parameter;
use practice\commands\parameters\SimpleParameter;
use practice\game\effects\PracticeEffect;
use practice\PracticeCore;
use practice\PracticeUtil;

class KitCommand extends BaseCommand
{
    public function __construct()
    {
        parent::__construct("kit", "The Base kit command.", "/kit help");
        $params = array(
            0 => array(
                new BaseParameter("help", parent::getPermission(), "Lists all the kit commands.", false),
            ),
            1 => array(
                new BaseParameter("create", parent::getPermission(), "Creates a new kit."),
                new SimpleParameter("name", Parameter::PARAMTYPE_STRING)
            ),
            2 => array(
                new BaseParameter("delete", parent::getPermission(), "Deletes a kit."),
                new SimpleParameter("name", Parameter::PARAMTYPE_STRING)
            ),
            3 => array(
                new BaseParameter("list", parent::getPermission(), "Lists all the kits in the server.", false)
            ),
            4 => array(
                new SimpleParameter("name", Parameter::PARAMTYPE_STRING, parent::getPermission(), "Gives the kit specified to the player that ran the command."),
            ),
            5 => array(
                new BaseParameter("item", parent::getPermission(), "Sets the represented item to the item in hand of the kit specified."),
                new SimpleParameter("name", Parameter::PARAMTYPE_STRING)
            ),
            6 => array(
                new BaseParameter("effect", parent::getPermission(), "Adds/removes an effect to the kit."),
                (new SimpleParameter("add:remove", Parameter::PARAMTYPE_STRING))->setExactValues(true),
                (new SimpleParameter("kit-name", Parameter::PARAMTYPE_STRING)),
                (new SimpleParameter("id", Parameter::PARAMTYPE_INTEGER)),
                (new SimpleParameter("amplifier", Parameter::PARAMTYPE_INTEGER))->setOptional(true),
                (new SimpleParameter("duration-seconds", Parameter::PARAMTYPE_INTEGER))->setOptional(true)
            )
        );
        $this->setParameters($params);
    }

    public function execute(CommandSender $sender, $commandLabel, array $args)
    {
        $msg = null;

        if(parent::canExecute($sender, $args)){
            $name = $args[0];
            switch($name){
                case "help":
                    $msg = $this->getFullUsage();
                    break;
                case "create":
                    $this->createKit($sender, $args[1]);
                    break;
                case "delete":
                    $this->deleteKit($sender, $args[1]);
                    break;
                case "list":
                    $msg = PracticeCore::getKitHandler()->getListKitMsg();
                    break;
                case "item":
                    $this->execRepItem($sender, strval($args[1]));
                    break;
                case "effect":
                    $cmd = $args[1];
                    $count = count($args);
                    if($cmd === "remove") {
                        if($count === 4) {
                            $this->removeEffect($sender, $args[2], intval($args[3]));
                        } else {
                            $msg = "Usage: /kit effect remove [kit-name:string] [id:int] - Removes an effect from a kit.";
                        }
                    } elseif ($cmd === "add") {
                        if($count === 6) {
                            $this->addEffect($sender, $args[2], intval($args[3]), intval($args[4]), intval($args[5]));
                        } else {
                            $msg = "Usage: /kit effect add [kit-name:string] [id:int] [amplifier:int] [duration:int] - Adds an effect to a kit.";
                        }
                    } else {
                        $msg = $this->getUsageOf($this->getParamGroupFrom($name), false);
                    }
                    break;
                default:
                    $this->giveKit($sender, $name);
            }
        }

        if(!is_null($msg)) $sender->sendMessage($msg);
        return true;
    }

    private function createKit(CommandSender $sender, $name) : void {
        $msg = null;
        if($sender instanceof Player){
            $str = strval($name);
            if(PracticeCore::getKitHandler()->isKit($str)){
                $msg = PracticeUtil::getMessage("general.kits.kits-exist");
                $msg = strval(str_replace("%kit%", $name, $msg));
            } else {
                PracticeCore::getKitHandler()->createKit($str, $sender->getPlayer());
                $msg = PracticeUtil::getMessage("general.kits.kit-create");
                $msg = strval(str_replace("%kit%", $name, $msg));
            }
        } else {
            $msg = PracticeUtil::getMessage("console-usage-command");
        }

        if(!is_null($msg)) $sender->sendMessage($msg);
    }

    private function deleteKit(CommandSender $sender, $name) : void {
        $msg = null;
        $str = strval($name);
        if(PracticeCore::getKitHandler()->isKit($str)){
            if(PracticeCore::getKitHandler()->removeKit($str)){
                PracticeCore::getItemHandler()->reload();
                $msg = PracticeUtil::getMessage("general.kits.kit-remove");
                $msg = strval(str_replace("%kit%", $name, $msg));
            } else {
                $msg = PracticeUtil::getMessage("general.kits.kit-no-exist");
                $msg = strval(str_replace("%kit%", $name, $msg));
            }
        } else {
            $msg = PracticeUtil::getMessage("general.kits.kit-no-exist");
            $msg = strval(str_replace("%kit%", $name, $msg));
        }

        if(!is_null($msg)) $sender->sendMessage($msg);
    }

    private function giveKit(CommandSender $sender, $name) : void {
        $msg = null;
        if($sender instanceof Player){
            $str = strval($name);
            if(PracticeCore::getPlayerHandler()->isPlayer($sender)){
                $player = PracticeCore::getPlayerHandler()->getPlayer($sender);
                if(PracticeCore::getKitHandler()->isKit($str)){
                    $kit = PracticeCore::getKitHandler()->getKit($str);
                    if(!$player->doesHaveKit()){
                        $kit->giveTo($player, true);
                    } else {
                        $msg = PracticeUtil::getMessage("general.kits.cooldown-msg");
                        $msg = strval(str_replace("%kit%", $str, $msg));
                    }
                } else {
                    $msg = PracticeUtil::getMessage("general.kits.kit-no-exist");
                    $msg = strval(str_replace("%kit%", $name, $msg));
                }
            }
        } else {
            $msg = PracticeUtil::getMessage("console-usage-command");
        }

        if(!is_null($msg)) $sender->sendMessage($msg);
    }

    private function execRepItem(CommandSender $sender, string $kitName) : void {
        $msg = null;
        if($sender instanceof Player) {
            if(PracticeCore::getKitHandler()->isKit($kitName)) {
                $kit = PracticeCore::getKitHandler()->getKit($kitName);
                $inv = $sender->getPlayer()->getInventory();
                $item = $inv->getItemInHand();
                if (!is_null($item) and $item->getId() !== 0) {
                    $kit = $kit->setRepItem($item);
                    PracticeCore::getKitHandler()->updateKit($kitName, $kit);
                    $msg = PracticeUtil::getMessage("general.kits.kit-update");
                } else {
                    $msg = PracticeUtil::getMessage("general.kits.no-item");
                }
            } else {
                $msg = PracticeUtil::getMessage("general.kits.kit-no-exist");
                $msg = strval(str_replace("%kit%", $kitName, $msg));
            }
        } else {
            $msg = PracticeUtil::getMessage("console-usage-command");
        }

        if(!is_null($msg)) $sender->sendMessage($msg);
    }

    private function addEffect(CommandSender $sender, string $kitName, int $id, int $amplifier, int $duration) : void {
        $msg = null;
        if(PracticeCore::getKitHandler()->isKit($kitName)) {
            $kit = PracticeCore::getKitHandler()->getKit($kitName);
            if(!is_null(Effect::getEffect($id))){
                $effect = new PracticeEffect(Effect::getEffect($id), $duration, $amplifier);
                if(!($kit->hasEffect($id))) {
                    $kit->addEffect($effect);
                    PracticeCore::getKitHandler()->updateKit($kitName, $kit);
                    $msg = PracticeUtil::getMessage("general.kits.kit-update");
                } else {
                    $msg = TextFormat::RED . "The kit already has an effect with an id of '$id'!";
                }
            } else {
                $msg = TextFormat::RED . "You have entered an invalid effect id!";
            }
        } else {
            $msg = PracticeUtil::getMessage("general.kits.kit-no-exist");
            $msg = strval(str_replace("%kit%", $kitName, $msg));
        }

        if(!is_null($msg)) $sender->sendMessage($msg);
    }

    private function removeEffect(CommandSender $sender, string $kitName, int $id) : void {
        $msg = null;
        if(PracticeCore::getKitHandler()->isKit($kitName)) {
            $kit = PracticeCore::getKitHandler()->getKit($kitName);
            if(!is_null(Effect::getEffect($id))) {
                if($kit->hasEffect($id)) {
                    $kit->removeEffect($id);
                    PracticeCore::getKitHandler()->updateKit($kitName, $kit);
                    $msg = PracticeUtil::getMessage("general.kits.kit-update");
                } else {
                    $msg = TextFormat::RED . "The kit doesn't have an effect with an id of '$id'!";
                }
            } else {
                $msg = TextFormat::RED . "You have entered an invalid effect id!";
            }
        } else {
            $msg = PracticeUtil::getMessage("general.kits.kit-no-exist");
            $msg = strval(str_replace("%kit%", $kitName, $msg));
        }

        if(!is_null($msg)) $sender->sendMessage($msg);
    }

}