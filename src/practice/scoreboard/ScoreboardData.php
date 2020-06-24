<?php

declare(strict_types=1);

namespace practice\scoreboard;


use pocketmine\Player;
use practice\PracticeCore;
use practice\scoreboard\display\ScoreboardDisplayLine;

class ScoreboardData
{

    const SCOREBOARD_SPAWN_DEFAULT = "scoreboard.spawn.default";
    const SCOREBOARD_SPAWN_QUEUE = "scoreboard.spawn.queue";
    const SCOREBOARD_FFA = "scoreboard.ffa";
    const SCOREBOARD_DUEL_PLAYER = "scoreboard.duel.player";
    const SCOREBOARD_DUEL_SPECTATOR = "scoreboard.duel.spectator";
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

        if($this->scoreboard instanceof Scoreboard)
        {
            $this->scoreboard->clearScoreboard();
        }
        else
        {
            // TODO: Get title.
            $this->scoreboard = new Scoreboard($this->player, "Practice");
        }

        $displayInfo = PracticeCore::getScoreboardDisplayManager()->getDisplayInfo($type);
        if(!$displayInfo instanceof ScoreboardDisplayInformation)
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
     * Updates the scoreboard according to the lines that have been updated.
     */
    public function update(): void
    {
        if($this->scoreboard !== null && $this->scoreboardType !== self::SCOREBOARD_NONE) {

            $scoreboardDisplayInfo = PracticeCore::getScoreboardDisplayManager()->getDisplayInfo($this->scoreboardType);
            if(!$scoreboardDisplayInfo instanceof ScoreboardDisplayInformation)
            {
                return;
            }

            $index = 0; $length = count($scoreboardDisplayInfo->getLines());
            while($index < $length)
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

                $index++;
            }
        }
    }
}