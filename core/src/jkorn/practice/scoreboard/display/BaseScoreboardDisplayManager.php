<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-07-22
 * Time: 18:33
 */

declare(strict_types=1);

namespace jkorn\practice\scoreboard\display;


use jkorn\practice\misc\AbstractManager;
use jkorn\practice\PracticeCore;
use jkorn\practice\scoreboard\display\manager\AbstractScoreboardDisplayManager;
use jkorn\practice\scoreboard\display\manager\ScoreboardDisplayManager;

class BaseScoreboardDisplayManager extends AbstractManager
{

    /** @var PracticeCore */
    private $core;

    /** @var AbstractScoreboardDisplayManager[] */
    private $managers = [];

    public function __construct(PracticeCore $core)
    {
        $this->core = $core;

        $this->initDefaultDisplays();

        parent::__construct(false);
    }

    /**
     * Initializes the default displays.
     */
    private function initDefaultDisplays(): void
    {
        $this->registerScoreboardManager(new ScoreboardDisplayManager($this->core));
    }

    /**
     * Loads the data needed for the manager, in this case it loads
     * the default scoreboard managers.
     *
     * @param bool $async
     */
    protected function load(bool $async = false): void
    {
        foreach($this->managers as $manager)
        {
            if(!$manager->isLoaded())
            {
                $manager->load();
            }
        }
    }

    /**
     * @param AbstractScoreboardDisplayManager $manager - The manager to add.
     * @param bool $load
     * @param bool $override
     *
     * Registers the scoreboard manager to the list of scoreboard managers.
     */
    public function registerScoreboardManager(AbstractScoreboardDisplayManager $manager, bool $load = false, bool $override = false): void
    {
        if(isset($this->managers[$manager->getLocalized()]) && !$override)
        {
            return;
        }

        $this->managers[$manager->getLocalized()] = $manager;
        $manager->onRegister();

        if($load && !$manager->isLoaded())
        {
            $manager->load();
        }
    }

    /**
     * @param string $name
     * @return ScoreboardDisplayInformation|null
     *
     * Gets the scoreboard display manager based on the name.
     */
    public function getDisplayInfo(string $name): ?ScoreboardDisplayInformation
    {
        foreach($this->managers as $manager)
        {
            $display = $manager->getDisplayInfo($name);

            if($display !== null)
            {
                return $display;
            }
        }

        return null;
    }


    /**
     * Saves the data from the manager, unused here.
     *
     * @param bool $async
     */
    public function save(bool $async = false): void {}
}