<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-20
 * Time: 17:10
 */

declare(strict_types=1);

namespace practice\commands\basic;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\entity\Attribute;
use pocketmine\entity\AttributeMap;
use pocketmine\Player;
use practice\PracticeCore;
use practice\PracticeUtil;

class FeedCommand extends Command
{

    public function __construct()
    {
        parent::__construct("feed", "Feed yourself or another player.", "Usage: /feed [target:player]", []);
        parent::setPermission("practice.permission.feed");
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

            if(PracticeUtil::testPermission($sender, $this->getPermission())) {

                $len = count($args);
                $player = null;

                if($len === 0){
                    if($sender instanceof Player){
                        $player = $sender->getPlayer();
                    } else {
                        $msg = PracticeUtil::getMessage("console-usage-command");
                    }
                } else if ($len === 1) {
                    $name = strval($args[0]);
                    if(PracticeCore::getPlayerHandler()->isPlayerOnline($name)){
                        $player = PracticeCore::getPlayerHandler()->getPlayer($name)->getPlayer();
                    } else {
                        $msg = PracticeUtil::getMessage("not-online");
                        $msg = strval(str_replace("%player-name%", $name, $msg));
                    }
                } else {
                    $msg = $this->getUsage();
                }

                if(!is_null($player)) {

                    $player->setFood($player->getMaxFood());
                    $player->setSaturation(Attribute::getAttribute(Attribute::SATURATION)->getMaxValue());

                    if($player->getName() === $sender->getName()) {
                        $msg = PracticeUtil::getMessage("general.feed.success-direct");
                    } else {
                        $msg = PracticeUtil::getMessage("general.feed.success-op");
                        $msg = strval(str_replace("%player%", $player->getName(), $msg));
                        $player->sendMessage(PracticeUtil::getMessage("general.feed.success-direct"));
                    }
                }
            }
        }

        if(!is_null($msg)) $sender->sendMessage($msg);
        return true;
    }
}