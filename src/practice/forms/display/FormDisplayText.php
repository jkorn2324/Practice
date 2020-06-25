<?php

declare(strict_types=1);

namespace practice\forms\display;


use pocketmine\Player;
use practice\forms\display\statistics\FormDisplayStatistic;
use practice\misc\IDisplayText;
use practice\PracticeUtil;

class FormDisplayText implements IDisplayText
{

    /** @var string */
    private $text;

    public function __construct(string $text = "")
    {
        $this->text = $text;
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
     * @param Player $player
     * @param mixed|null $args
     * @return string
     *
     * Gets the converted text.
     */
    public function getText(Player $player, $args = null): string
    {
        $text = $this->text;

        FormDisplayStatistic::convert($text, $player, $args);
        PracticeUtil::convertMessageColors($text);

        return $text;
    }
}