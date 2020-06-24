<?php

declare(strict_types=1);

namespace practice\scoreboard\display;


use pocketmine\Player;
use practice\PracticeUtil;
use practice\scoreboard\display\statistics\ScoreboardStatistic;

class ScoreboardDisplayLine
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
}