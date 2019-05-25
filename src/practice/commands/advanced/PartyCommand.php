<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-24
 * Time: 13:08
 */

declare(strict_types=1);

namespace practice\commands\advanced;

use MongoDB\Driver\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use practice\commands\BaseCommand;
use practice\commands\parameters\BaseParameter;
use practice\commands\parameters\Parameter;
use practice\commands\parameters\SimpleParameter;
use practice\game\FormUtil;
use practice\PracticeCore;
use practice\PracticeUtil;

class PartyCommand extends BaseCommand
{

    public function __construct()
    {
        parent::__construct('party', 'The base party command.', '/party help');

        $parameters = [
            0 => [
                new BaseParameter('help', $this->getPermission(), 'Lists all the party commands.', false),
            ],
            1 => [
                new BaseParameter('create', $this->getPermission(), 'Create a party.')
            ],
            2 => [
                new BaseParameter('invite', $this->getPermission(), 'Invites a player to your party.', false),
                new SimpleParameter('player', Parameter::PARAMTYPE_TARGET)
            ],
            3 => [
                new BaseParameter('kick', $this->getPermission(), 'Allows a party leader to kick a player from their party.', false),
                new SimpleParameter('player', Parameter::PARAMTYPE_TARGET)
            ],
            4 => [
                new BaseParameter('leave', $this->getPermission(), 'Allows a player to leave a party.', false)
            ],
            5 => [
                new BaseParameter('accept', $this->getPermission(), 'Allows player to accept a party request.', false),
                new SimpleParameter('player', Parameter::PARAMTYPE_TARGET)
            ],
            6 => [
                new BaseParameter('join', $this->getPermission(), 'Allows a player to join an open party.', false),
                new SimpleParameter('player', Parameter::PARAMTYPE_TARGET)
            ],
            7 => [
                new BaseParameter('open', $this->getPermission(), 'Allows a player to open their party so that players can join without invites.', false)
            ],
            8 => [
                new BaseParameter('close', $this->getPermission(), 'Allows a player to close their party so that players can only join with an invite.', false)
            ]
            /*5 => [
                new BaseParameter('players', $this->getPermission(), 'Lists all the players in the current party.', false);
            ]*/
        ];
        $this->setParameters($parameters);
    }

    public function execute(CommandSender $sender, $commandLabel, array $args) {

        $msg = null;

        if($this->canExecute($sender, $args)) {

            $name = strval($args[0]);

            $check = ['help' => true, 'create' => true, 'invite' => true, 'kick' => true, 'leave' => true, 'accept' => true, 'join' => true, 'open' => true, 'close' => true];

            if(array_key_exists($name, $check) and PracticeUtil::canExecutePartyCmd($sender, $name)) {

                switch($name) {
                    case 'help':
                        $msg = $this->getFullUsage();
                        break;
                    case 'create':
                        $this->createParty($sender);
                        break;
                    case 'invite':
                        $this->invitePlayer($sender, strval($args[1]));
                        break;
                    case 'kick':
                        $this->kickPlayer($sender, strval($args[1]));
                        break;
                    case 'leave':
                        $this->leaveParty($sender);
                        break;
                    case 'accept':
                        $this->acceptRequest($sender, strval($args[1]));
                        break;
                }

            }
        }

        if(!is_null($msg)) $sender->sendMessage($msg);

        return true;
    }

    private function createParty(CommandSender $sender) : void {

        if(PracticeCore::getPlayerHandler()->isPlayerOnline($sender->getName())) {
            $p = PracticeCore::getPlayerHandler()->getPlayer($sender->getName());
            $form = FormUtil::createPartyForm();
            $p->sendForm($form);
        }
    }

    private function invitePlayer(CommandSender $sender, string $invited) : void {

        $msg = null;

        if(PracticeCore::getPlayerHandler()->isPlayerOnline($sender->getName()) and PracticeCore::getPlayerHandler()->isPlayerOnline($invited)) {

            if($sender->getName() === $invited)
                $msg = PracticeUtil::getMessage('party.invite.yourself');
                //$msg = TextFormat::RED . "You can't invite yourself to your own party.";

            else {

                $p = PracticeCore::getPlayerHandler()->getPlayer($sender->getName());
                $invited = PracticeCore::getPlayerHandler()->getPlayer($invited);

                PracticeCore::getPartyManager()->invitePlayer($p, $invited->getPlayerName());
            }

        } else $msg = PracticeUtil::str_replace(PracticeUtil::getMessage('not-online'), ["%player%" => $invited]);
        //$msg = TextFormat::RED . $invited . ' is not online!';

        if(!is_null($msg)) $sender->sendMessage($msg);
    }

    private function kickPlayer(CommandSender $sender, string $player) : void {

        $msg = null;

        if(PracticeCore::getPlayerHandler()->isPlayerOnline($sender->getName()) and PracticeCore::getPlayerHandler()->isPlayerOnline($player)) {

            $party = PracticeCore::getPartyManager()->getPartyFromLeader($sender->getName());

            if($party->isInParty($player)) {

                PracticeCore::getPartyManager()->removePlayerFromParty($player, true);

            } else $msg = PracticeUtil::str_replace(PracticeUtil::getMessage('party.kick.not-party'), ["%player%" => $player]);
                //$msg = TextFormat::RED . $player . ' is not in your party!';

        } else $msg = PracticeUtil::str_replace(PracticeUtil::getMessage('not-online'), ["%player%" => $player]);

        if(!is_null($msg)) $sender->sendMessage($msg);
    }

    private function leaveParty(CommandSender $sender) : void {

        if(PracticeCore::getPlayerHandler()->isPlayerOnline($sender->getName()))
            PracticeCore::getPartyManager()->removePlayerFromParty($sender->getName());
    }

    private function acceptRequest(CommandSender $sender, string $player) : void {

        $msg = null;

        if(PracticeCore::getPlayerHandler()->isPlayerOnline($sender->getName()) and PracticeCore::getPlayerHandler()->isPlayerOnline($player)) {

            $senderOfRq = PracticeCore::getPlayerHandler()->getPlayer($player);

            if(PracticeCore::getPartyManager()->hasPendingInvite($senderOfRq->getPlayerName(), $sender->getName())) {

                $invite = PracticeCore::getPartyManager()->getPendingInvite($player, $sender->getName());

                PracticeCore::getPartyManager()->addPlayerToParty($invite);

            } else $msg = PracticeUtil::str_replace(PracticeUtil::getMessage('party.invite.no-pending-rq'), ['%player%' => $player]);
                //$msg = TextFormat::RED . 'You do not have any pending requests from ' . $player . '.';
        } else $msg = PracticeUtil::str_replace(PracticeUtil::getMessage('not-online'), ["%player%" => $player]);
            //$msg = TextFormat::RED . $player . 'is not online!';

        if(!is_null($msg)) $sender->sendMessage($msg);
    }
}