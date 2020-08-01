<?php

declare(strict_types=1);


namespace jkorn\practice\forms\internal;

use jkorn\practice\forms\internal\types\kits\CreateKitForm;
use jkorn\practice\forms\internal\types\kits\DeleteKitForm;
use jkorn\practice\forms\internal\types\kits\edit\EditKitEffects;
use jkorn\practice\forms\internal\types\kits\edit\EditKitItems;
use jkorn\practice\forms\internal\types\kits\edit\EditKitKnockback;
use jkorn\practice\forms\internal\types\kits\edit\EditKitMenu;
use jkorn\practice\forms\internal\types\kits\KitManagerMenu;
use jkorn\practice\forms\internal\types\kits\KitSelectorMenu;
use jkorn\practice\forms\IPracticeForm;

/**
 * Class InternalForms
 * @package jkorn\practice\forms\internal
 *
 * This class handles the internal forms or forms that can't be customized.
 */
class InternalForms implements IInternalFormIDs
{

    /** @var IPracticeForm[] */
    private static $forms = [];

    /**
     * Initializes the default forms.
     */
    public static function initDefaults(): void
    {
        self::registerForm(new KitManagerMenu());
        self::registerForm(new KitSelectorMenu());
        self::registerForm(new CreateKitForm());
        self::registerForm(new DeleteKitForm());
        self::registerForm(new EditKitMenu());
        self::registerForm(new EditKitItems());
        self::registerForm(new EditKitKnockback());
        self::registerForm(new EditKitEffects());
    }

    /**
     * @param IInternalForm $form - The form class we are registering.
     * @param bool $override - Determines whether we should override the default form or not
     *                         if it already exists.
     *
     * Registers the form to the forms list.
     */
    public static function registerForm(IInternalForm $form, bool $override = false): void
    {
        if(isset(self::$forms[$name = $form->getLocalizedName()]) && !$override)
        {
            return;
        }

        self::$forms[$name] = $form;
    }

    /**
     * @param string $name
     * @return IPracticeForm|null
     *
     * Gets the default form based on its name.
     */
    public static function getForm(string $name): ?IInternalForm
    {
        if(isset(self::$forms[$name]))
        {
            return self::$forms[$name];
        }

        return null;
    }
}