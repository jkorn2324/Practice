<?php

declare(strict_types=1);

namespace jkorn\bd\forms;


use jkorn\bd\BasicDuels;
use jkorn\bd\forms\internal\BasicDuelArenaMenu;
use jkorn\bd\forms\internal\BasicDuelArenaSelector;
use jkorn\bd\forms\internal\edit\EditBasicDuelArenaArea;
use jkorn\bd\forms\internal\edit\EditBasicDuelArenaMenu;
use jkorn\bd\forms\internal\edit\EditBasicDuelArenaVisibility;
use jkorn\practice\forms\display\manager\AbstractFormDisplayManager;
use jkorn\practice\forms\internal\InternalForm;


class BasicDuelsFormManager extends AbstractFormDisplayManager
{

    const NAME = "basic.duels.display";

    // Form constants.
    const TYPE_SELECTOR_FORM = "form.selector.type.duel";
    const KIT_SELECTOR_FORM = "form.selector.type.kit";

    /** @var BasicDuels */
    private $core;

    public function __construct(BasicDuels $core)
    {
        $this->core = $core;

        parent::__construct($core->getResourcesFolder() . "forms", $core->getDataFolder() . "forms");
    }

    /**
     * @return string
     *
     * Gets the localized name of the form display manager.
     */
    public function getLocalizedName(): string
    {
        return self::NAME;
    }

    /**
     * Initializes the internal forms to the display manager.
     */
    protected function initInternalForms(): void
    {
        InternalForm::registerForm(new BasicDuelArenaMenu(), true);
        InternalForm::registerForm(new BasicDuelArenaSelector(), true);
        InternalForm::registerForm(new EditBasicDuelArenaMenu(), true);
        InternalForm::registerForm(new EditBasicDuelArenaVisibility(), true);
        InternalForm::registerForm(new EditBasicDuelArenaArea(), true);
    }
}