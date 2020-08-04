<?php

declare(strict_types=1);

namespace jkorn\ffa\forms;


use jkorn\ffa\FFAAddon;
use jkorn\ffa\forms\internal\CreateFFAArena;
use jkorn\ffa\forms\internal\DeleteFFAArena;
use jkorn\ffa\forms\internal\EditFFAArena;
use jkorn\ffa\forms\internal\FFAArenaMenu;
use jkorn\ffa\forms\internal\FFAArenaSelector;
use jkorn\practice\forms\display\manager\AbstractFormDisplayManager;
use jkorn\practice\forms\internal\InternalForm;

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

        $this->registerInternalForms();

        parent::__construct($core->getResourcesFolder() . "forms/", $core->getDataFolder() . "forms/");
    }

    /**
     * Registers the internal forms.
     */
    private function registerInternalForms(): void
    {
        InternalForm::registerForm(new FFAArenaSelector());
        InternalForm::registerForm(new FFAArenaMenu());
        InternalForm::registerForm(new EditFFAArena());
        InternalForm::registerForm(new CreateFFAArena());
        InternalForm::registerForm(new DeleteFFAArena());
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