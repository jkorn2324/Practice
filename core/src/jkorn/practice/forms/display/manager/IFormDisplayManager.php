<?php

declare(strict_types=1);


namespace jkorn\practice\forms\display\manager;


use jkorn\practice\forms\display\FormDisplay;

interface IFormDisplayManager
{

    /**
     * @param string $name
     * @return FormDisplay|null
     *
     * Gets the form from the display.
     */
    public function getForm(string $name): ?FormDisplay;

    /**
     * @return bool
     *
     * Determines if the form display manager has been loaded.
     */
    public function didLoad(): bool;

    /**
     * Loads the forms to the forms list.
     *
     * @param bool $async
     */
    public function load(bool $async): void;

    /**
     * @return string
     *
     * Gets the localized name of the form display manager.
     */
    public function getLocalizedName(): string;
}