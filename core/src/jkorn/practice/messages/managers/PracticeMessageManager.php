<?php

declare(strict_types=1);

namespace jkorn\practice\messages\managers;


use jkorn\practice\messages\IPracticeMessages;
use jkorn\practice\PracticeCore;

class PracticeMessageManager extends AbstractMessageManager implements IPracticeMessages
{

    const NAME = "practice.messages";

    /** @var PracticeCore */
    private $core;

    public function __construct(PracticeCore $core)
    {
        $this->core = $core;
        parent::__construct($core->getResourcesFolder() . "messages/", $core->getDataFolder() . "messages/");
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