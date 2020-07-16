<?php

declare(strict_types=1);

namespace jkorn\practice\player\info;


class ClicksInfo
{

    /** @var array */
    private $clicks;
    /** @var int */
    private $lastCPS = 0;

    public function __construct()
    {
        $this->clicks = [];
    }

    /**
     * Updates the array clicks.
     */
    public function update(): void
    {
        $currentMillis = (int)round(microtime(true) * 1000);
        foreach($this->clicks as $millis => $value)
        {
            $difference = $currentMillis - $millis;
            // Determines if the difference is greater than 1 second.
            if($difference >= 1000)
            {
                unset($this->clicks[$millis]);
            }
        }
    }

    /**
     * @param bool $clickedBlock
     *
     * Adds a click to the clicks information based on previous actions.
     */
    public function addClick(bool $clickedBlock): void
    {
        $currentMillis = (int)round(microtime(true) * 1000);
        $this->clicks[$currentMillis] = $clickedBlock;
    }

    /**
     * @return int
     *
     * Gets the cps of the player.
     */
    public function getCps(): int
    {
        $this->lastCPS = count($this->clicks);
        $this->update();
        return count($this->clicks);
    }

    /**
     * @return int
     *
     * Gets the last cps of the player.
     */
    public function getLastCPS(): int
    {
        return $this->lastCPS;
    }
}