<?php

declare(strict_types=1);

namespace jkorn\bd;


use jkorn\bd\gen\BasicDuelsGeneratorInfo;
use jkorn\bd\gen\types\RedDefault;
use jkorn\bd\gen\types\YellowDefault;
use jkorn\practice\level\gen\PracticeGeneratorManager;
use jkorn\practice\player\info\StatsInfo;
use jkorn\practice\PracticeCore;
use jkorn\practice\scoreboard\display\statistics\ScoreboardStatistic;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class BasicDuels extends PluginBase
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

        // Initializes the generators.
        self::initGenerators();

        // Register the game manager to the practice core game manager.
        PracticeCore::getBaseGameManager()->registerGameManager(
            new BasicDuelsManager()
        );
    }

    /**
     * @param string ...$dependencies
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

    /**
     * Initializes the generators.
     */
    private static function initGenerators(): void
    {
        PracticeGeneratorManager::registerGenerator(new BasicDuelsGeneratorInfo(RedDefault::class));
        PracticeGeneratorManager::registerGenerator(new BasicDuelsGeneratorInfo(YellowDefault::class));
    }

    /**
     * Initializes the player statistics.
     */
    private static function initPlayerStatistics(): void
    {
        // TODO: Add statistic information.

        ScoreboardStatistic::registerStatistic(new ScoreboardStatistic(
            "duels.basic.stat.wins",
            function(Player $player, Server $server)
            {
                // TODO: Get the statistics.
                return 0;
            })
        );
    }
}