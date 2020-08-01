<?php

declare(strict_types=1);

namespace jkorn\ffa\scoreboards;


use jkorn\ffa\FFAAddon;
use jkorn\practice\scoreboard\display\manager\AbstractScoreboardDisplayManager;

class FFAScoreboardManager extends AbstractScoreboardDisplayManager
{
    const MANAGER_NAME = "ffa.scoreboard.manager";

    // The Scoreboard.
    const FFA_SCOREBOARD = "ffa.scoreboard";

    /** @var FFAAddon */
    private $core;

    public function __construct(FFAAddon $core)
    {
        $this->core = $core;

        parent::__construct($core->getResourcesFolder() . "scoreboards/", $core->getDataFolder() . "scoreboards/");
    }

    /**
     * Called when the display manager is registered.
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
        return self::MANAGER_NAME;
    }
}