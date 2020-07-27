<?php

declare(strict_types=1);

namespace jkorn\practice\messages;


use jkorn\practice\display\DisplayStatistic;
use jkorn\practice\misc\IDisplayText;
use jkorn\practice\PracticeUtil;
use pocketmine\Player;

class PracticeMessage implements IDisplayText
{

    /** @var string */
    private $localizedName;
    /** @var string */
    private $rawText;

    public function __construct(string $localizedName, string $rawText)
    {
        $this->localizedName = $localizedName;
        $this->rawText = $rawText;
    }

    /**
     * @return string
     *
     * Gets the localized name.
     */
    public function getLocalizedName(): string
    {
        return $this->localizedName;
    }

    /**
     * @param Player $player
     * @param mixed|null $args
     * @return string
     *
     * Gets the text from based on the player & arguments.
     */
    public function getText(Player $player, $args = null): string
    {
        $output = $this->rawText;

        PracticeUtil::convertMessageColors($output);
        DisplayStatistic::convert($output, $player, $args);

        return $output;
    }
}