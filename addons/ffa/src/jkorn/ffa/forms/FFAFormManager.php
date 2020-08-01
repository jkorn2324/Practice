<?php

declare(strict_types=1);

namespace jkorn\ffa\forms;


use jkorn\ffa\FFAAddon;
use jkorn\practice\forms\display\manager\AbstractFormDisplayManager;

class FFAFormManager extends AbstractFormDisplayManager
{

    const LOCALIZED_NAME = "ffa.display.form.manager";

    // The ffa play form.
    const FFA_PLAY_FORM = "form.ffa.play";

    /** @var FFAAddon */
    private $core;

    /**
     * FFAFormManager constructor.
     * @param FFAAddon $core
     *
     * The constructor for the ffa form manager.
     */
    public function __construct(FFAAddon $core)
    {
        $this->core = $core;

        parent::__construct($core->getResourcesFolder() . "forms/", $core->getDataFolder() . "forms/");
    }

    /**
     * @return string
     *
     * Gets the localized name of the form display manager.
     */
    public function getLocalizedName(): string
    {
        return self::LOCALIZED_NAME;
    }
}