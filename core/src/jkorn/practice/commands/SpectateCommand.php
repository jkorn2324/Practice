<?php

declare(strict_types=1);

namespace jkorn\practice\commands;


use jkorn\practice\forms\display\manager\PracticeFormManager;
use jkorn\practice\player\PracticePlayer;
use jkorn\practice\PracticeCore;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class SpectateCommand extends PracticeCommand implements IPracticePermissions
{

    public function __construct()
    {
        parent::__construct("spectate", "This command allows players to spectate a game.", "Usage: /spectate", ["spec"]);
        parent::setPermission(self::SPECTATE);
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * Called when the command is executed.
     */
    protected function onExecute(CommandSender $sender, string $commandLabel, array $args): void
    {
        $form = PracticeCore::getBaseFormDisplayManager()->getForm(PracticeFormManager::FORM_SPECTATOR_SELECTION);
        if($form !== null && $sender instanceof Player) {
            $form->display($sender);
        } else {
            // TODO: Send error message.
        }
    }

    /**
     * @param CommandSender $target
     * @return bool
     *
     * Used to test the permission
     */
    public function testPermission(CommandSender $target): bool
    {
        if(!$target instanceof Player)
        {
            // TODO: Console usage command.
            return false;
        }

        // Checks permission before player can execute command.
        if(!parent::testPermission($target))
        {
            return false;
        }

        if($target instanceof PracticePlayer)
        {
            // TODO: Send game message.
            if($target->isInGame())
            {
                return false;
            }

            // TODO: Send spectator message.
            if($target->isSpectator())
            {
                return false;
            }
        }

        return true;
    }
}