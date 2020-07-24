<?php

declare(strict_types=1);

namespace jkorn\practice\commands;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;

abstract class PracticeCommand extends Command
{

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param string[] $args
     *
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if($this->testPermission($sender))
        {
            $this->onExecute($sender, $commandLabel, $args);
        }

        return true;
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     *
     * Called when the command is executed.
     */
    abstract protected function onExecute(CommandSender $sender, string $commandLabel, array $args): void;
}