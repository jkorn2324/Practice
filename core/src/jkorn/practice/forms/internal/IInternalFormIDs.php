<?php


namespace jkorn\practice\forms\internal;


use jkorn\practice\forms\IPracticeForm;

/**
 * Interface IInternalFormIDs
 * @package jkorn\practice\forms\internal
 *
 * Only used to store the ids of the internal forms.
 */
interface IInternalFormIDs
{

    // Constants for the names of the internal forms.
    const KIT_MANAGER_MENU = "kit.manager.menu";
    const KIT_SELECTOR = "kit.edit.selector";

    const CREATE_KIT_FORM = "kit.create.menu";
    const EDIT_KIT_MENU = "kit.edit.menu";
    const DELETE_KIT_FORM = "kit.delete.menu";
    const VIEW_KIT_FORM = "kit.view.menu";

    // Edits the kit information.
    const EDIT_KIT_ITEMS = "kit.edit.items";
    const EDIT_KIT_KNOCKBACK = "kit.edit.knockback";
    const EDIT_KIT_EFFECTS = "kit.edit.effects";
}