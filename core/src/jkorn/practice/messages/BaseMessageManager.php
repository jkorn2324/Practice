<?php


namespace jkorn\practice\messages;


use jkorn\practice\messages\managers\AbstractMessageManager;
use jkorn\practice\messages\managers\PracticeMessageManager;
use jkorn\practice\misc\AbstractManager;
use jkorn\practice\PracticeCore;

/**
 * Class BaseMessageManager
 * @package jkorn\practice\messages
 *
 * This class handles the messages send to the players.
 */
class BaseMessageManager extends AbstractManager
{

    /** @var AbstractMessageManager[] */
    private $messageManagers = [];

    /** @var PracticeCore */
    private $core;

    public function __construct(PracticeCore $core)
    {
        $this->core = $core;

        $this->registerDefaults();

        parent::__construct(false);
    }

    /**
     * Registers the default managers.
     */
    private function registerDefaults(): void
    {
        $this->register(new PracticeMessageManager($this->core));
    }

    /**
     * @param AbstractMessageManager $manager
     * @param bool $load - Determines whether to load the manager on register.
     * @param bool $override - Determines whether to override localized messages in the other manager.
     *
     * Registers the message manager to the list.
     */
    public function register(AbstractMessageManager $manager, bool $load = false, bool $override = false): void
    {
        if(isset($this->messageManagers[$manager->getLocalizedName()]) && !$override)
        {
            return;
        }

        $this->messageManagers[$manager->getLocalizedName()] = $manager;
        $manager->onRegister();

        if($load && !$manager->isLoaded())
        {
            $manager->load();
        }
    }

    /**
     * Loads the data needed for the manager.
     *
     * @param bool $async
     */
    protected function load(bool $async = false): void
    {
        foreach($this->messageManagers as $manager)
        {
            if(!$manager->isLoaded())
            {
                $manager->load();
            }
        }
    }

    /**
     * @param string $localized
     * @return AbstractMessageManager|null
     *
     * Gets the message manager based on the localized name.
     */
    public function getMessageManager(string $localized): ?AbstractMessageManager
    {
        if(isset($this->messageManagers[$localized]))
        {
            return $this->messageManagers[$localized];
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