<?php

declare(strict_types=1);

namespace jkorn\bd\scoreboards;


use jkorn\bd\BasicDuels;
use jkorn\practice\scoreboard\display\manager\AbstractScoreboardDisplayManager;

class BasicDuelsScoreboardManager extends AbstractScoreboardDisplayManager
{
    const NAME = "duels.basic.scoreboards";

    // Scoreboard types:
    const TYPE_SCOREBOARD_SPAWN_QUEUE = "scoreboard.spawn.queue";
    const TYPE_SCOREBOARD_DUEL_1VS1_PLAYER = "scoreboard.duel.player.1vs1";
    const TYPE_SCOREBOARD_DUEL_TEAM_PLAYER = "scoreboard.duel.player.teams";
    const TYPE_SCOREBOARD_DUEL_SPECTATOR = "scoreboard.duel.spectator";

    /** @var BasicDuels */
    private $core;

    public function __construct(BasicDuels $core)
    {
        $this->core = $core;

        parent::__construct($core->getResourcesFolder() . "scoreboards/", $core->getDataFolder() . "scoreboards/");
    }

    /**
     * Called when the display manager is registered,
     * unused here.
     */
    public function onRegister(): void {}

    /**
     * @return string
     *
     * Gets the localized name of the display manager, used
     * to distinguish from each other.
     */
    public function getLocalized(): string
    {
        return self::NAME;
    }
}