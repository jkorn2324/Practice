<?php

declare(strict_types=1);

namespace jkorn\practice\scoreboard;


use jkorn\practice\games\misc\teams\TeamColor;
use jkorn\practice\player\info\settings\SettingsInfo;
use jkorn\practice\player\PracticePlayer;
use pocketmine\Player;
use jkorn\practice\PracticeCore;
use jkorn\practice\scoreboard\display\ScoreboardDisplayLine;
use pocketmine\utils\TextFormat;

class ScoreboardData
{

    // Default scoreboard information.
    const SCOREBOARD_SPAWN_DEFAULT = "scoreboard.spawn.default";
    const SCOREBOARD_FFA = "scoreboard.ffa";
    // const SCOREBOARD_DUEL_PLAYER = "scoreboard.duel.player";
    const SCOREBOARD_NONE = "scoreboard.none";

    /** @var string */
    private $scoreboardType;
    /** @var Player|PracticePlayer */
    private $player;

    /** @var Scoreboard|null */
    private $scoreboard;

    public function __construct(Player $player, string $scoreboardType)
    {
        $this->scoreboardType = $scoreboardType;
        $this->player = $player;

        $this->scoreboard = null;

        $this->setScoreboard($scoreboardType);
    }

    /**
     * @param string $type
     *
     * Sets the scoreboard according to the type.
     */
    public function setScoreboard(string $type): void
    {

        // Don't do anything is anyone tries to set the
        // scoreboard as none.
        if($type === self::SCOREBOARD_NONE)
        {
            return;
        }

        $currentType = $this->getScoreboard();
        if($currentType === self::SCOREBOARD_NONE)
        {
            $this->removeScoreboard();
            $this->scoreboardType = $type;
            return;
        }

        // Makes sure we don't send a duplicate.
        if(
            $type === $this->scoreboardType
            && $this->scoreboard !== null
            && !$this->scoreboard->isRemoved()
        )
        {
            $this->scoreboardType = $type;
            return;
        }

        // Gets the title based on display information.
        $title = "Practice";
        $displayInfo = PracticeCore::getBaseScoreboardDisplayManager()->getDisplayInfo($type);
        if($displayInfo !== null)
        {
            $title = $displayInfo->getTitle()->getText($this->player);
        }

        if($this->scoreboard !== null)
        {
            if($this->scoreboard->isRemoved())
            {
                $this->scoreboard->resendScoreboard($title);
            }

            $this->scoreboard->clearScoreboard();
        }
        else
        {
            $this->scoreboard = new Scoreboard($this->player, $title);
        }

        if($displayInfo === null)
        {
            $this->removeScoreboard();
            $this->scoreboardType = self::SCOREBOARD_NONE;
            return;
        }

        $lines = $displayInfo->getLines();

        $linesToAdd = [];
        $linesTextInput = [];

        foreach($lines as $index => $line)
        {
            if(!$line->isLine()) {
                $linesTextInput[] = $text = $line->getText($this->player);
                $this->scoreboard->addLine($index, $text);
            } else {
                $linesToAdd[$index] = $line;
            }
        }

        // Add the lines to the line text.
        $previousColor = "";
        foreach($linesToAdd as $index => $line) {

            do {
                // Gets a random color.
                $color = TeamColor::random()->getTextColor();
            } while ($previousColor === $color);
            $previousColor = $color;
            $lineText = $previousColor . TextFormat::RESET . $line->getTextForLine(...$linesTextInput);
            $this->scoreboard->addLine($index, $lineText);
        }

        $this->scoreboardType = $type;
    }

    /**
     * @return string
     *
     * Gets the scoreboard type.
     */
    public function getScoreboard(): string
    {
        $settingsInfo = $this->player->getSettingsInfo();
        $property = $settingsInfo->getProperty(SettingsInfo::SCOREBOARD_DISPLAY);
        if($property !== null)
        {
            $enabled = $property->getValue();
            if(!$enabled)
            {
                return self::SCOREBOARD_NONE;
            }
        }

        return $this->scoreboardType;
    }

    /**
     * Reloads the scoreboard.
     */
    public function reloadScoreboard(): void
    {
        $this->setScoreboard($this->scoreboardType);
    }

    /**
     * Removes the scoreboard.
     */
    private function removeScoreboard(): void
    {
        if($this->scoreboard !== null && !$this->scoreboard->isRemoved()) {
            $this->scoreboard->removeScoreboard();
        }
    }

    /**
     * Updates the scoreboard according to the lines are different.
     */
    public function update(): void
    {
        $scoreboardType = $this->getScoreboard();
        if($this->scoreboard !== null && $scoreboardType !== self::SCOREBOARD_NONE) {

            $scoreboardDisplayInfo = PracticeCore::getBaseScoreboardDisplayManager()->getDisplayInfo($this->scoreboardType);
            if($scoreboardDisplayInfo === null)
            {
                return;
            }

            // Updates the lines instead.
            $updatedLines = $scoreboardDisplayInfo->getUpdateLines();
            foreach($updatedLines as $index)
            {
                $lineText = $this->scoreboard->getLine($index);
                if($lineText === null || trim($lineText) === "")
                {
                    continue;
                }

                $updatedLine = $scoreboardDisplayInfo->getLine($index);
                if(!$updatedLine instanceof ScoreboardDisplayLine)
                {
                    continue;
                }

                $uLineText = $updatedLine->getText($this->player);
                if($uLineText !== $lineText)
                {
                    $this->scoreboard->addLine($index, $uLineText);
                }
            }
        }
    }
}