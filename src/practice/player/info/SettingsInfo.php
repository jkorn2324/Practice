<?php

declare(strict_types=1);

namespace practice\player\info;


use practice\misc\ISavedHeader;

class SettingsInfo implements ISavedHeader
{

    const SCOREBOARD_ENABLED = "scoreboardEnabled";
    const PE_ONLY = "peOnly";
    const TAP_ITEMS_ENABLED = "tapItems";

    /** @var bool */
    private $peOnly;
    /** @var bool */
    private $scoreboardEnabled;
    /** @var bool */
    private $tapItemsEnabled;

    /**
     * SettingsInfo constructor.
     * @param bool $peOnly - Determines whether the settings are pe only.
     * @param bool $scoreboardEnabled - Determines whether the scoreboard is enabled.
     * @param bool $tapItemsEnabled - Determines whether the tap items are enabled or not (for pe players).
     */
    public function __construct(bool $peOnly = false, bool $scoreboardEnabled = true, bool $tapItemsEnabled = true)
    {
        $this->peOnly = $peOnly;
        $this->scoreboardEnabled = $scoreboardEnabled;
        $this->tapItemsEnabled = $tapItemsEnabled;
    }

    /**
     * @return bool
     *
     * Determines whether or not the player has PE-Only queues enabled.
     */
    public function isPeOnly(): bool
    {
        return $this->peOnly;
    }

    /**
     * @param bool $peOnly
     *
     * Determines whether or not the player has PE-Only queues enabled.
     */
    public function setPEOnly(bool $peOnly): void
    {
        $this->peOnly = $peOnly;
    }

    /**
     * @return bool
     *
     * Determines whether or not the scoreboards are enabled.
     */
    public function isScoreboardEnabled(): bool
    {
        return $this->scoreboardEnabled;
    }

    /**
     * @param bool $enabled
     *
     * Sets the scoreboard as enabled.
     */
    public function setScoreboardEnabled(bool $enabled): void
    {
        $this->scoreboardEnabled = $enabled;
    }

    /**
     * @return bool
     *
     * Determines if tap items are enabled for the player.
     */
    public function isTapItemsEnabled(): bool
    {
        return $this->tapItemsEnabled;
    }

    /**
     * @param bool $enabled
     *
     * Sets tap items as enabled.
     */
    public function setTapItemsEnabled(bool $enabled): void
    {
        $this->tapItemsEnabled = $enabled;
    }

    /**
     * @return array - The exported settings.
     * Exports the settings info to an array.
     */
    public function export(): array
    {
        return [
            self::SCOREBOARD_ENABLED => $this->scoreboardEnabled,
            self::PE_ONLY => $this->peOnly,
            self::TAP_ITEMS_ENABLED => $this->tapItemsEnabled
        ];
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
     * @param array $data -> Address to the data.
     * @param $playerSettings -> Address to the settings data.
     *
     * Extracts the settings from the data information.
     */
    public static function extract(array &$data, &$playerSettings)
    {
        if(isset($data[$header = "settings"]))
        {
            $peOnly = false; $scoreboardEnabled = true; $tapItemsEnabled = true;

            $settings = $data[$header];

            if(isset($settings[self::SCOREBOARD_ENABLED]))
            {
                $scoreboardEnabled = (bool)$settings[self::SCOREBOARD_ENABLED];
            }

            if(isset($settings[self::PE_ONLY]))
            {
                $peOnly = (bool)$settings[self::SCOREBOARD_ENABLED];
            }

            if(isset($settings[self::TAP_ITEMS_ENABLED]))
            {
                $tapItemsEnabled = (bool)$settings[self::TAP_ITEMS_ENABLED];
            }

            if($playerSettings instanceof SettingsInfo)
            {
                $playerSettings->scoreboardEnabled = $scoreboardEnabled;
                $playerSettings->peOnly = $peOnly;
                $playerSettings->tapItemsEnabled = $tapItemsEnabled;
                return;
            }

            $playerSettings = new SettingsInfo($scoreboardEnabled, $peOnly, $tapItemsEnabled);
            return;
        }

        $playerSettings = new SettingsInfo();
    }

}