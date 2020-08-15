<?php

declare(strict_types=1);

namespace jkorn\practice\forms\internal;


use jkorn\practice\forms\internal\types\arenas\ArenaMenu;
use jkorn\practice\forms\internal\types\kits\CreateKitForm;
use jkorn\practice\forms\internal\types\kits\DeleteKitForm;
use jkorn\practice\forms\internal\types\kits\edit\EditKitIcon;
use jkorn\practice\forms\internal\types\kits\edit\EditKitKnockback;
use jkorn\practice\forms\internal\types\kits\edit\EditKitMenu;
use jkorn\practice\forms\internal\types\kits\edit\effects\AddKitEffect;
use jkorn\practice\forms\internal\types\kits\edit\effects\EditKitEffect;
use jkorn\practice\forms\internal\types\kits\edit\effects\EditKitEffectsMenu;
use jkorn\practice\forms\internal\types\kits\edit\effects\KitEffectSelectorMenu;
use jkorn\practice\forms\internal\types\kits\edit\effects\RemoveKitEffect;
use jkorn\practice\forms\internal\types\kits\KitManagerMenu;
use jkorn\practice\forms\internal\types\kits\KitSelectorMenu;
use jkorn\practice\forms\IPracticeForm;
use jkorn\practice\player\PracticePlayer;
use pocketmine\Player;

abstract class InternalForm implements IPracticeForm, IInternalFormIDs
{

    /** @var InternalForm[] */
    private static $forms = [];

    /** @var bool - Determines whether the internal form is initialized. */
    private static $initialized = false;

    /**
     * Initializes the default internal forms.
     */
    private static function initDefaults(): void
    {
        self::registerForm(new KitManagerMenu());
        self::registerForm(new KitSelectorMenu());
        self::registerForm(new CreateKitForm());
        self::registerForm(new DeleteKitForm());
        self::registerForm(new EditKitMenu());
        self::registerForm(new EditKitKnockback());
        self::registerForm(new EditKitEffectsMenu());
        self::registerForm(new KitEffectSelectorMenu());
        self::registerForm(new RemoveKitEffect());
        self::registerForm(new AddKitEffect());
        self::registerForm(new EditKitEffect());
        self::registerForm(new EditKitIcon());
        self::registerForm(new ArenaMenu());

        self::$initialized = true;
    }

    /**
     * @param InternalForm $form - The form class we are registering.
     * @param bool $override - Determines whether we should override the default form or not
     *                         if it already exists.
     *
     * Registers the form to the forms list.
     */
    public static function registerForm(InternalForm $form, bool $override = false): void
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
    public static function getForm(string $name): ?InternalForm
    {
        // Initializes the forms.
        if(!self::$initialized)
        {
            self::initDefaults();
        }

        if(isset(self::$forms[$name]))
        {
            return self::$forms[$name];
        }

        return null;
    }

    // --------------------------------- The instance of the Internal Form -------------------------

    /**
     * @param Player $player
     * @param mixed ...$args
     *
     * Displays the form to the player.
     */
    public function display(Player $player, ...$args): void
    {
        if(!$this->testPermission($player))
        {
            return;
        }

        if($player instanceof PracticePlayer)
        {
            if($player->isInGame())
            {
                // TODO: Send message.
                return;
            }

            if($player->isSpectatingGame())
            {
                // TODO: Send message.
                return;
            }
        }

        $this->onDisplay($player, ...$args);
    }

    /**
     * @param Player $player
     * @param mixed ...$args
     *
     * Called when the display method first occurs.
     */
    abstract protected function onDisplay(Player $player, ...$args): void;

    /**
     * @param Player $player
     * @return bool
     *
     * Tests the form's permissions to see if the player can use it.
     */
    abstract protected function testPermission(Player $player): bool;

    /**
     * @return string
     *
     * Gets the localized name of the internal form.
     */
    abstract public function getLocalizedName(): string;
}