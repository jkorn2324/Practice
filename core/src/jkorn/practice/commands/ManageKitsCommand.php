<?php

declare(strict_types=1);

namespace jkorn\practice\commands;


use jkorn\practice\forms\internal\InternalForms;
use jkorn\practice\player\PracticePlayer;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class ManageKitsCommand extends PracticeCommand implements IPracticePermissions
{
    /**
     * The manage kits command constructor.
     */
    public function __construct()
    {
        parent::__construct("kits", "Command that shows the kits menu.", "Usage: /kits", ["managekits"]);
        parent::setPermission(self::MANAGE_KITS);
    }

    /**
     * @param CommandSender|Player $sender
     * @param string $commandLabel
     * @param array $args
     *
     * Called when the command is executed.
     */
    protected function onExecute(CommandSender $sender, string $commandLabel, array $args): void
    {
        $form = InternalForms::getForm(InternalForms::KIT_MANAGER_MENU);
        if($form !== null && $sender instanceof Player)
        {
            $form->display($sender);
        }
        else
        {
            // TODO: Send error message.
        }
    }

    /**
     * @param CommandSender $target
     * @return bool
     *
     * Test the permission.
     */
    public function testPermission(CommandSender $target): bool
    {
        if(!$target instanceof Player)
        {
            return false;
        }

        if(
            $target instanceof PracticePlayer
            && $target->isInGame()
        )
        {
            // TODO: Usage in game message.
            return  false;
        }

        return parent::testPermission($target);
    }
}