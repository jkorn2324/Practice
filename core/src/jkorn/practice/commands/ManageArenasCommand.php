<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-08-03
 * Time: 19:00
 */

declare(strict_types=1);

namespace jkorn\practice\commands;


use jkorn\practice\forms\internal\InternalForm;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class ManageArenasCommand extends PracticeCommand implements IPracticePermissions
{

    public function __construct()
    {
        parent::__construct("arenas", "Command that shows the arenas menu.", "Usage: /arenas", ["managearenas"]);
        parent::setPermission(self::MANAGE_ARENAS);
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
        $form = InternalForm::getForm(InternalForm::ARENA_MENU_MANAGER);
        var_dump($form);
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
            // TODO: Console message.
            return false;
        }

        // Checks if the player has the permission first.
        if(!parent::testPermission($target))
        {
            return false;
        }

        return true;
    }
}