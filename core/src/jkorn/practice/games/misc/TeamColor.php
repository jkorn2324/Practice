<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-07-23
 * Time: 23:40
 */

declare(strict_types=1);

namespace jkorn\practice\games\misc;

use pocketmine\utils\TextFormat;

/**
 * Class TeamColor
 * @package jkorn\practice\games\misc
 *
 * Used to distinguish teams from one another.
 */
class TeamColor
{

    /** @var TeamColor[] */
    private static $teamColors = [];

    /**
     * Initializes the team colors.
     */
    private static function initialize(): void
    {
        self::registerTeamColor(new TeamColor("Gold", TextFormat::GOLD));
        self::registerTeamColor(new TeamColor("Red", TextFormat::RED));
        self::registerTeamColor(new TeamColor("Green", TextFormat::GREEN));
        self::registerTeamColor(new TeamColor("Aqua", TextFormat::BLUE));
        self::registerTeamColor(new TeamColor("Light Purple", TextFormat::LIGHT_PURPLE));
        self::registerTeamColor(new TeamColor("Dark Purple", TextFormat::DARK_PURPLE));
        self::registerTeamColor(new TeamColor("Yellow", TextFormat::YELLOW));
        self::registerTeamColor(new TeamColor("Blue", TextFormat::BLUE));
        self::registerTeamColor(new TeamColor("White", TextFormat::WHITE));
        self::registerTeamColor(new TeamColor("Dark Blue", TextFormat::DARK_BLUE));
        self::registerTeamColor(new TeamColor("Gray", TextFormat::GRAY));
    }

    /**
     * @param TeamColor $color
     *
     * It registers the team color to the list.
     */
    private static function registerTeamColor(TeamColor $color): void
    {
        self::$teamColors[$color->getColorName()] = $color;
    }

    /**
     * @return TeamColor
     *
     * Generates a random team color based on the list.
     */
    public static function random(): TeamColor
    {
        if(count(self::$teamColors) <= 0)
        {
            self::initialize();
        }

        $randomKey = array_keys(self::$teamColors)[mt_rand(0, count(self::$teamColors))];
        return self::$teamColors[$randomKey];
    }

    // ----------------------------- Team Color Instance -----------------------------


    /** @var string */
    private $colorName;

    /** @var string */
    private $textColor;

    public function __construct(string $colorName, string $textColor)
    {
        $this->colorName = $colorName;
        $this->textColor = $textColor;
    }

    /**
     * @return string
     *
     * Gets the text color (text format color).
     */
    public function getTextColor(): string
    {
        return $this->textColor;
    }

    /**
     * @return string
     *
     * Gets the color name.
     */
    public function getColorName(): string
    {
        return $this->colorName;
    }

    /**
     * @param $object
     * @return bool
     *
     * Determines if an object is equivalent to another object.
     */
    public function equals($object): bool
    {
        if($object instanceof TeamColor)
        {
            return $object->colorName === $this->colorName
                && $object->textColor === $this->textColor;
        }

        return false;
    }
}