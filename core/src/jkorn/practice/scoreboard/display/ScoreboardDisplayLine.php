<?php

declare(strict_types=1);

namespace jkorn\practice\scoreboard\display;


use jkorn\practice\display\DisplayStatistic;
use pocketmine\Player;
use jkorn\practice\misc\IDisplayText;
use jkorn\practice\PracticeUtil;
use pocketmine\utils\TextFormat;

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
     * @param string ...$lines - The lines of the scoreboard.
     * @return string
     *
     * Gets the text for the line element. ONLY USED IF THE
     * SCOREBOARD DISPLAY LINE IS IN FACT A LINE.
     */
    public function getTextForLine(string...$lines): string
    {
        if(!$this->isLine())
        {
            return $this->text;
        }

        $maxCharacters = 0;
        foreach($lines as $line) {
            $cleaned = trim(TextFormat::clean($line));
            if(strlen($cleaned) >= $maxCharacters) {
                $maxCharacters = strlen($cleaned);
            }
        }
        return str_repeat("-", $maxCharacters);
    }

    /**
     * @return bool
     *
     * Determines if the scoreboard display line is a line element.
     */
    public function isLine(): bool
    {
        return strpos($this->text, "{LINE}") !== false;
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