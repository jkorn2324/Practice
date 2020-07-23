<?php

declare(strict_types=1);

namespace jkorn\practice\scoreboard\display\manager;


use jkorn\practice\PracticeCore;

class ScoreboardDisplayManager extends AbstractScoreboardDisplayManager
{

    const NAME = "display.default";

    /** @var PracticeCore */
    private $core;

    public function __construct(PracticeCore $core)
    {
        $this->core = $core;

        parent::__construct(
            $core->getResourcesFolder() . "scoreboards",
            $core->getDataFolder() . "scoreboards");
    }

    /**
     * Called when the display manager is registered,
     * unused in this case.
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