<?php

declare(strict_types=1);

namespace practice\scoreboard\display;


use pocketmine\Player;
use practice\misc\IDisplayText;
use practice\PracticeUtil;
use practice\scoreboard\display\statistics\ScoreboardStatistic;

class ScoreboardDisplayLine implements IDisplayText
{

    /** @var string */
    private $text;

    /** @var ScoreboardStatistic[] */
    private $statistics;

    public function __construct(string $text = "")
    {
        $this->text = $text;
        $this->statistics = [];
    }

    /**
     * @return string
     *
     * Gets the raw text.
     */
    public function getRawText(): string
    {
        return $this->text;
    }

    /**
     * @param Player $player -> The text based on the display line.
     * @return string
     *
     * Gets the text from the scoreboard.
     */
    public function getText(Player $player): string
    {
        $output = $this->text;

        PracticeUtil::convertMessageColors($output);
        ScoreboardStatistic::convert($player, $output);

        return $output;
    }

    /**
     * @return bool
     *
     * Determines whether or not to update the line.
     */
    public function containsStatistics(): bool
    {
        return ScoreboardStatistic::containsStatistics($this->text);
    }

}