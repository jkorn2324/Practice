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

    /**
     * @param $data
     * @return FormDisplayText|null
     *
     * Decodes the button from a set of data.
     */
    public static function decodeButton($data): ?FormDisplayText
    {
        if(is_string($data))
        {
            $exploded = explode("\n", $data);
            switch(count($exploded))
            {
                case 0:
                    return new FormDisplayText();
                case 1:
                    return self::decodeButton(["top.text" => trim($data[0]), "bottom.text" => ""]);
            }

            return self::decodeButton(["top.text" => trim($data[0]), "bottom.text" => trim($data[1])]);
        }
        elseif (is_array($data))
        {
            if(isset($data["top.text"], $data["bottom.text"]))
            {
                $text = self::trimTextForButtonLines($data["top.text"]);
                $buttonText = self::trimTextForButtonLines($data["bottom.text"]);
                if($buttonText !== "")
                {
                    $text .= "\n" . $buttonText;
                }
                return new FormDisplayText($text);
            }
        }
        return null;
    }

    /**
     * @param string $text - The input text.
     * @return string
     *
     * Trims the text to format for buttons, accounts for statistics as well.
     */
    private static function trimTextForButtonLines(string $text): string
    {
        if(trim($text) === "")
        {
            return "";
        }

        $tokenized = TextFormat::tokenize(trim($text));
        $newText = "";
        $characterCount = 0;
        $bold = false;

        foreach($tokenized as $token)
        {
            $clearedStatisticToken = DisplayStatistic::clearStatistics($token);

            if(strlen(TextFormat::clean($clearedStatisticToken)) === 0)
            {
                switch($clearedStatisticToken)
                {
                    case TextFormat::RESET:
                        $bold = false;
                        break;
                    case TextFormat::BOLD:
                        $bold = true;
                        break;
                }
                $newText += $token;
                continue;
            }

            if($characterCount >= (self::MAX_BUTTON_TEXT_LENGTH - 3))
            {
                return $newText . TextFormat::RESET . "...";
            }

            $additiveCharacters = strlen($clearedStatisticToken);
            if($bold)
            {
                $additiveCharacters *= 2;
            }

            $characterCount += $additiveCharacters;
            $newText += $token;
        }

        return $newText;
    }
}