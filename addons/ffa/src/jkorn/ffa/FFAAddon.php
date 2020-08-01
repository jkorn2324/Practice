<?php

declare(strict_types=1);

namespace jkorn\ffa;


use jkorn\practice\PracticeCore;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class FFAAddon extends PluginBase
{
    /**
     * Called when the plugin is enabled.
     */
    public function onEnable()
    {
        // Checks the dependencies and makes sure that they exist.
        if(!self::checkDependencies("Practice"))
        {
            return;
        }

        // Register the game manager to the practice core game manager.
        PracticeCore::getBaseGameManager()->registerGameManager(
            new FFAGameManager($this)
        );
    }

    /**
     * @return string
     *
     * Gets the resources folder.
     */
    public function getResourcesFolder(): string
    {
        return $this->getFile() . "resources/";
    }

    /**
     * @param string...$dependencies
     * @return bool
     *
     * Checks the dependencies of various plugins.
     */
    private static function checkDependencies(string...$dependencies): bool {

        $server = Server::getInstance();
        foreach($dependencies as $dependency) {
            $plugin = $server->getPluginManager()->getPlugin($dependency);
            if($plugin === null) {
                return false;
            }
        }
        return true;
    }
}