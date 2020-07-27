<?php

declare(strict_types=1);

namespace jkorn\bd\messages;


use jkorn\bd\BasicDuels;
use jkorn\practice\messages\managers\AbstractMessageManager;

class BasicDuelsMessageManager extends AbstractMessageManager
{

    const NAME = "basic.duels.message.manager";

    /** @var BasicDuels */
    private $core;

    public function __construct(BasicDuels $core)
    {
        $this->core = $core;

        parent::__construct($core->getResourcesFolder() . "messages", $core->getDataFolder() . "messages");
    }

    /**
     * Called when the message manager is registered.
     */
    public function onRegister(): void {}

    /**
     * @return string
     *
     * Gets the localized name of the abstract message manager.
     */
    public function getLocalizedName(): string
    {
        return self::NAME;
    }
}