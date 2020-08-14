<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-08-14
 * Time: 15:27
 */

declare(strict_types=1);

namespace jkorn\practice\forms\display;


use jkorn\practice\display\DisplayStatistic;
use jkorn\practice\PracticeUtil;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class ButtonDisplayText extends FormDisplayText
{

    /** @var string */
    private $shortButtonText;

    public function __construct(string $longButtonText = "", string $shortButtonText = "")
    {
        parent::__construct($longButtonText);

        $this->shortButtonText = $shortButtonText;
    }

    /**
     * @param Player $player
     * @param mixed|null $args - The arguments for the button.
     * @param bool $texture - Determines whether or not the texture is enabled, affects
     *                        whether or not the length of the text was is fit for texture or not.
     * @return string
     *
     * Gets the converted text.
     */
    public function getText(Player $player, $args = null, bool $texture = true): string
    {
        if(!$texture) {
            return parent::getText($player, $args);
        }

        $text = $this->shortButtonText;

        DisplayStatistic::convert($text, $player, $args);
        PracticeUtil::convertMessageColors($text);

        return $text;
    }

    /**
     * @param $data
     * @return ButtonDisplayText|null
     *
     * Decodes the button from a set of data.
     */
    public static function decode($data): ?ButtonDisplayText
    {
        if(is_string($data))
        {
            $exploded = explode("\n", $data);
            switch(count($exploded))
            {
                case 0:
                    return new ButtonDisplayText();
                case 1:
                    return self::decode(["top.text" => trim($data[0]), "bottom.text" => ""]);
            }

            return self::decode(["top.text" => trim($data[0]), "bottom.text" => trim($data[1])]);
        }
        elseif (is_array($data))
        {
            if(isset($data["top.text"], $data["bottom.text"]))
            {
                $longText = self::trimTextForButtonLines($data["top.text"]);
                $bottomText = self::trimTextForButtonLines($data["bottom.text"]);
                if($bottomText !== "")
                {
                    $longText .= "\n" . $bottomText;
                }

                $shortText = self::trimTextForButtonLines($data["top.text"], 25);
                $bottomText = self::trimTextForButtonLines($data["bottom.text"], 25);
                if($bottomText !== "")
                {
                    $shortText .= "\n" . $bottomText;
                }

                return new ButtonDisplayText($longText, $shortText);
            }
        }
        return null;
    }

    /**
     * @param string $text - The input text.
     * @param int $lineLength - The line length of the button display.
     * @return string
     *
     * Trims the text to format for buttons, accounts for statistics as well.
     */
    private static function trimTextForButtonLines(string $text, int $lineLength = self::MAX_BUTTON_TEXT_LENGTH): string
    {
        if(trim($text) === "")
        {
            return "";
        }

        $tokenized = PracticeUtil::tokenizeColors($text);
        $newText = "";
        $characterCount = 0.0; // Can use decimals here as we are measuring.
        $bold = false;

        foreach($tokenized as $token)
        {
            $token = strval($token);
            if(PracticeUtil::isColor($token, false))
            {
                switch($token)
                {
                    case "{RESET}":
                        $bold = false;
                        break;
                    case "{BOLD}":
                        $bold = true;
                        break;
                }
                $newText .= $token;
                continue;
            }

            $exploded = explode(" ", $token);
            foreach ($exploded as $explodedPart)
            {
                $clearedStatisticExploded = DisplayStatistic::clearStatistics($explodedPart . " ");
                if($characterCount > $lineLength)
                {
                    return substr(trim($newText), 0, $lineLength) . TextFormat::RESET . TextFormat::DARK_GRAY . "...";
                }

                $additiveCharacters = strlen($clearedStatisticExploded);
                if($bold)
                {
                    $additiveCharacters *= 1.25;
                }

                $characterCount += $additiveCharacters;
                $newText .= $explodedPart . " ";
            }
        }

        return trim($newText);
    }
}