<?php

declare(strict_types=1);

namespace jkorn\practice\scoreboard;


use pocketmine\Player;
use jkorn\practice\PracticeCore;
use jkorn\practice\scoreboard\display\ScoreboardDisplayLine;

class ScoreboardData
{

    // Default scoreboard information.
    const SCOREBOARD_SPAWN_DEFAULT = "scoreboard.spawn.default";
    const SCOREBOARD_FFA = "scoreboard.ffa";
    // const SCOREBOARD_DUEL_PLAYER = "scoreboard.duel.player";
    const SCOREBOARD_NONE = "scoreboard.none";

    /** @var string */
    private $scoreboardType;
    /** @var Player */
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
    public function setScoreboard(string $type): void {

        if($type === self::SCOREBOARD_NONE)
        {
            $this->removeScoreboard();
            $this->scoreboardType = $type;
            return;
        }

        if($this->scoreboard !== null)
        {
            if($this->scoreboard->isRemoved())
            {
                $this->scoreboard->resendScoreboard();
            }

            $this->scoreboard->clearScoreboard();
        }
        else
        {
            // TODO: Get title.
            $this->scoreboard = new Scoreboard($this->player, "Practice");
        }

        $displayInfo = PracticeCore::getScoreboardDisplayManager()->getDisplayInfo($type);
        if($displayInfo === null)
        {
            $this->removeScoreboard();
            $this->scoreboardType = self::SCOREBOARD_NONE;
            return;
        }

        $lines = $displayInfo->getLines();
        foreach($lines as $index => $line)
        {
            $this->scoreboard->addLine($index, $line->getText($this->player));
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
        if($this->scoreboard !== null) {
            $this->scoreboard->removeScoreboard();
        }
    }

    /**
     * Updates the scoreboard according to the lines are different.
     */
    public function update(): void
    {
        if($this->scoreboard !== null && $this->scoreboardType !== self::SCOREBOARD_NONE) {

            $scoreboardDisplayInfo = PracticeCore::getScoreboardDisplayManager()->getDisplayInfo($this->scoreboardType);
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