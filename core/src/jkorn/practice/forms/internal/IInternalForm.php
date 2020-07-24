<?php

declare(strict_types=1);

namespace jkorn\practice\forms\internal;


use jkorn\practice\forms\IPracticeForm;

interface IInternalForm extends IPracticeForm, IInternalFormIDs
{

    /**
     * @return string
     *
     * Gets the localized name of the practice form.
     */
    public function getLocalizedName(): string;
}