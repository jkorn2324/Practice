<?php

declare(strict_types=1);

namespace jkorn\practice\player\info\settings;


use jkorn\practice\misc\ISavedHeader;
use jkorn\practice\player\info\settings\properties\BooleanSettingProperty;

/**
 * Class SettingsInfo
 * @package jkorn\practice\player\info\settings
 *
 * Handles the types of settings found in the player.
 */
class SettingsInfo implements ISavedHeader
{

    const SCOREBOARD_DISPLAY = "scoreboard.display";
    const TAP_ITEMS = "tap.items";
    const SWISH_SOUNDS_ENABLED = "swish.sounds";


    /** @var bool - Determines if settings were initialized. */
    private static $initialized = false;

    /** @var array */
    private static $settingsList = [];

    /**
     * @param string $localized - The localized name of the property.
     * @param $propertyType - The property type of the setting, should be a class.
     * @param array $displayInfo - The specific form display information.
     * @param ?$defaultValue - The specific value of the property, determines the type of property.
     * @param bool $override - Overrides the setting.
     *
     * Registers the settings to the settings list.
     */
    public static function registerSetting(string $localized, $propertyType, array $displayInfo, $defaultValue = null, bool $override = false): void
    {
        if (!$override && isset(self::$settingsList[$localized])) {
            return;
        }

        if (!is_subclass_of($propertyType, ISettingsProperty::class)) {
            return;
        }

        self::$settingsList[$localized] = ["value" => $defaultValue, "class" => $propertyType, "display" => $displayInfo ?? []];
    }

    /**
     * @param string $localized
     *
     * Unregisters the setting from the settings list.
     */
    public static function unregisterSetting(string $localized): void
    {
        if (isset(self::$settingsList[$localized])) {
            unset(self::$settingsList[$localized]);
        }
    }

    /**
     * Initializes the settings.
     */
    public static function init(): void
    {
        // Registers the settings to the information.

        self::registerSetting(self::SCOREBOARD_DISPLAY, BooleanSettingProperty::class,
            [
                "enabled" => "Enable Scoreboard",
                "disabled" => "Disable Scoreboard"
            ], true);

        self::registerSetting(self::SWISH_SOUNDS_ENABLED, BooleanSettingProperty::class,
            [
                "enabled" => "Enable Swish Sounds",
                "disabled" => "Disable Swish Sounds"
            ], true);

        self::registerSetting(self::TAP_ITEMS, BooleanSettingProperty::class,
            [
                "enabled" => "Enable Tap Items",
                "disabled" => "Disable Tap Items"
            ], true);

        self::$initialized = true;
    }

    /**
     * @return array - The output array.
     *
     * Gets the settings form display information.
     */
    public static function getSettingsFormDisplay()
    {
        $displayInfo = [];

        foreach (self::$settingsList as $localized => $data) {
            if (isset($data["display"])) {
                $formDisplay = $data["display"];
                $displayInfo[$localized] = $formDisplay;
            }
        }

        return $displayInfo;
    }

    /**
     * @return ISettingsProperty[]
     *
     * Gets a new settings property list based on the given list.
     */
    private static function getSettings()
    {
        $settings = [];

        foreach (self::$settingsList as $localizedName => $data) {
            $defaultValue = $data["value"];
            $typeClass = $data["class"];
            $display = $data["display"];

            if ($defaultValue !== null) {
                /** @var ISettingsProperty $setting */
                $setting = new $typeClass($localizedName, $display, $defaultValue);
            } else {
                /** @var ISettingsProperty $setting */
                $setting = new $typeClass($localizedName, $display);
            }

            $settings[$setting->getLocalized()] = $setting;
        }

        return $settings;
    }


    // ----------------------------------- Settings Information Instance ----------------------------------

    /** @var ISettingsProperty[] */
    private $settingsProperties = [];

    /**
     * SettingsInfo constructor.
     */
    public function __construct()
    {
        $this->settingsProperties = self::getSettings();
    }

    /**
     * @return array - The exported settings.
     * Exports the settings info to an array.
     */
    public function export(): array
    {
        $exported = [];

        foreach ($this->settingsProperties as $localized => $property) {
            $exported[$property->getLocalized()] = $property->getValue();
        }

        return $exported;
    }

    /**
     * @param string $localized
     * @return ISettingsProperty|null
     *
     * Gets the property based on the localized name.
     */
    public function getProperty(string $localized): ?ISettingsProperty
    {
        if (isset($this->settingsProperties[$localized])) {
            return $this->settingsProperties[$localized];
        }

        return null;
    }

    /**
     * @return array|ISettingsProperty[]|null
     *
     * Gets the settings misc.
     */
    public function getProperties()
    {
        return $this->settingsProperties;
    }

    /**
     * Gets the player settings header.
     * @return string - The header.
     */
    public function getHeader()
    {
        return "settings";
    }

    /**
     * @param $data -> Address to the data.
     * @param $playerSettings -> Address to the settings data.
     *
     * Extracts the settings from the data information.
     */
    public static function extract(&$data, &$playerSettings)
    {
        if (!$playerSettings instanceof SettingsInfo) {
            $playerSettings = new SettingsInfo();
            self::extract($data, $playerSettings);
            return;
        }

        // Determines if the data contains the settings header.
        if (!is_array($data) || !isset($data[$header = "settings"])) {
            return;
        }

        // Extracts the settings from the data.
        $dataSettings = $data[$header];
        foreach ($playerSettings->settingsProperties as $localizedName => $property) {
            if (isset($dataSettings[$localizedName])) {
                $property->setValue($dataSettings[$localizedName]);
            }
        }
    }

}