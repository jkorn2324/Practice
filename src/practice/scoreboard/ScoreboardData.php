<?php

declare(strict_types=1);

namespace practice\scoreboard;


use pocketmine\Player;

class ScoreboardData
{

    const SCOREBOARD_SPAWN = "scoreboard.spawn";
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

        switch($type)
        {
            case self::SCOREBOARD_SPAWN:
                // TODO
                break;
        }
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
     * @param int $id
     * @param string $line
     *
     * Updates the line accordingly.
     */
    public function updateLine(int $id, string $line): void
    {
        if($this->scoreboard !== null && $this->scoreboardType !== self::SCOREBOARD_NONE) {
            $this->scoreboard->addLine($id, $line);
        }
    }
}