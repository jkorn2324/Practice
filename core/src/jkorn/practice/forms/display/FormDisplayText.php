<?php

declare(strict_types=1);

namespace jkorn\practice\forms\display;


use jkorn\practice\display\DisplayStatistic;
use pocketmine\Player;
use jkorn\practice\misc\IDisplayText;
use jkorn\practice\PracticeUtil;
use pocketmine\utils\TextFormat;

class FormDisplayText implements IDisplayText
{

    const MAX_BUTTON_TEXT_LENGTH = 30;

    /** @var string */
    private $text;

    public function __construct(string $text = "")
    {
        $this->text = $text;
    }

    /**
     * @param Player $player
     * @param mixed|null $args
     * @return string
     *
     * Gets the converted text.
     */
    public function getText(Player $player, $args = null): string
    {
        $text = $this->text;

        DisplayStatistic::convert($text, $player, $args);
        PracticeUtil::convertMessageColors($text);

        return $text;
    }
}