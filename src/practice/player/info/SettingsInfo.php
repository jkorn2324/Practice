<?php

declare(strict_types=1);

namespace practice\player\info;


use practice\misc\ISavedHeader;

class SettingsInfo implements ISavedHeader
{

    const SCOREBOARD_ENABLED = "scoreboardEnabled";
    const PE_ONLY = "peOnly";

    /** @var bool */
    private $peOnly;
    /** @var bool */
    private $scoreboardEnabled;

    /**
     * SettingsInfo constructor.
     * @param bool $peOnly - Determines whether the settings are pe only.
     * @param bool $scoreboardEnabled - Determines whether the scoreboard is enabled.
     */
    public function __construct(bool $peOnly = false, bool $scoreboardEnabled = true)
    {
        $this->peOnly = $peOnly;
        $this->scoreboardEnabled = $scoreboardEnabled;
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
     * @return array - The exported settings.
     * Exports the settings info to an array.
     */
    public function export(): array
    {
        return [
            self::SCOREBOARD_ENABLED => $this->scoreboardEnabled,
            self::PE_ONLY => ""
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
            $peOnly = false; $scoreboardEnabled = true;

            $settings = $data[$header];

            if(isset($settings[self::SCOREBOARD_ENABLED]))
            {
                $scoreboardEnabled = (bool)$settings[self::SCOREBOARD_ENABLED];
            }

            if(isset($settings[self::PE_ONLY]))
            {
                $peOnly = (bool)$settings[self::SCOREBOARD_ENABLED];
            }

            if($playerSettings instanceof SettingsInfo)
            {
                $playerSettings->scoreboardEnabled = $scoreboardEnabled;
                $playerSettings->peOnly = $peOnly;
                return;
            }

            $playerSettings = new SettingsInfo($scoreboardEnabled, $peOnly);
            return;
        }

        $playerSettings = new SettingsInfo();
    }

}