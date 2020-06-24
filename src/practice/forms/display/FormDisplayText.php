<?php


namespace practice\forms\display;


use pocketmine\Player;
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
     * @return string
     *
     * Gets the converted text.
     */
    public function getText(Player $player): string
    {
        $text = $this->text;

        // TODO: Convert Form statistics.
        PracticeUtil::convertMessageColors($text);

        return $text;
    }
}