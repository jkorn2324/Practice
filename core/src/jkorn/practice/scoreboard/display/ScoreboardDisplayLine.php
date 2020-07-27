<?php

declare(strict_types=1);

namespace jkorn\practice\scoreboard\display;


use jkorn\practice\display\DisplayStatistic;
use pocketmine\Player;
use jkorn\practice\misc\IDisplayText;
use jkorn\practice\PracticeUtil;

class ScoreboardDisplayLine implements IDisplayText
{

    /** @var string */
    private $text;

    /** @var DisplayStatistic[] */
    private $statistics;

    public function __construct(string $text = "")
    {
        $this->text = $text;
        $this->statistics = [];
    }

    /**
     * @param Player $player -> The text based on the display line.
     * @param mixed $args -> The arguments for the text.
     * @return string
     *
     * Gets the text from the scoreboard.
     */
    public function getText(Player $player, $args = null): string
    {
        $output = $this->text;

        PracticeUtil::convertMessageColors($output);
        DisplayStatistic::convert($output, $player, $args);

        return $output;
    }

    /**
     * @return bool
     *
     * Determines whether or not to update the line.
     */
    public function containsStatistics(): bool
    {
        return DisplayStatistic::containsStatistics($this->text, true);
    }

}