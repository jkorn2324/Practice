<?php

declare(strict_types=1);

namespace jkorn\bd\forms;


use jkorn\bd\BasicDuels;
use jkorn\practice\forms\display\FormDisplay;
use jkorn\practice\forms\display\manager\AbstractFormDisplayManager;


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
}