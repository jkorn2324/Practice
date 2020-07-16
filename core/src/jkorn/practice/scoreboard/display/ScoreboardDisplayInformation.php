<?php

declare(strict_types=1);

namespace jkorn\practice\scoreboard\display;

class ScoreboardDisplayInformation
{

    /** @var ScoreboardDisplayLine[] */
    private $lines;

    /** @var string */
    private $localizedName;

    /** @var int[] */
    private $updatedLines;

    public function __construct(string $localized, array &$data)
    {
        $this->localizedName = $localized;
        $this->lines = [];
        $this->updatedLines = [];

        $this->initDisplay($data);
    }

    /**
     * @param array $data - The data for the scoreboard.
     *
     * Initializes the display information.
     */
    public function initDisplay(array &$data): void
    {
        $length = 0;
        foreach($data as $line => $text)
        {
            // Should start the line at 0.
            $scoreboardLine = intval(str_replace("line-", "", $line));
            // Checks if its an empty line.
            if($text == "")
            {
                $text = str_repeat(" ", $scoreboardLine);
            }

            $this->lines[$lineNumber = $scoreboardLine - 1] = $lineDisplay = new ScoreboardDisplayLine($text);

            if($lineDisplay->containsStatistics())
            {
                $this->updatedLines[] = $lineNumber;
            }

            if($scoreboardLine > $length)
            {
                $length = $scoreboardLine;
            }
        }

        $filledArray = array_fill(0, $length - 1, new ScoreboardDisplayLine());
        $this->lines = array_replace($filledArray, $this->lines);
    }

    /**
     * @return ScoreboardDisplayLine[]
     *
     * Gets the scoreboard lines.
     */
    public function getLines()
    {
        return $this->lines;
    }

    /**
     * @return array|int[]
     *
     * Gets the lines that should be updated.
     */
    public function getUpdateLines()
    {
        return $this->updatedLines;
    }

    /**
     * @param int $index
     * @return ScoreboardDisplayLine|null
     *
     * Gets the scoreboard line information based on the index.
     */
    public function getLine(int $index)
    {
        $index %= count($this->lines);
        if(isset($this->lines[$index]))
        {
            return $this->lines[$index];
        }

        return null;
    }
}