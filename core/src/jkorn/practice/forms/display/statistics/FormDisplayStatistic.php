<?php

declare(strict_types=1);

namespace jkorn\practice\forms\display\statistics;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use jkorn\practice\arenas\types\ffa\FFAArena;
use jkorn\practice\kits\SavedKit;

class FormDisplayStatistic
{
    /** @var string */
    protected $localizedName;
    /** @var callable */
    protected $callable;

    /** @var Server */
    protected $server;

    public function __construct(string $localizedName, callable $callable)
    {
        $this->localizedName = $localizedName;
        $this->callable = $callable;

        $this->server = Server::getInstance();
    }

    /**
     * @return string
     *
     * The localized name of the statistic.
     */
    public function getLocalizedName(): string
    {
        return $this->localizedName;
    }

    /**
     * @param Player $player
     * @param mixed|null $data
     * @return mixed
     *
     * Gets the value from the player.
     */
    public function getValue(Player $player, $data = null)
    {
        $callable = $this->callable;
        return $callable($player, $this->server, $data);
    }


    // ----------------------------------------------------------------------------------

    const STATISTIC_KIT_NAME = "kit";

    /** @var FormDisplayStatistic[] */
    private static $statistics = [];

    /**
     * @param FormDisplayStatistic $stat - The statistic.
     * @param bool $override - Determines whether or not to override it or not.
     *
     * Registers the statistic to the list.
     */
    public static function registerStatistic(FormDisplayStatistic $stat, bool $override = false): void
    {
        if(!$override && isset(self::$statistics[$stat->getLocalizedName()]))
        {
            return;
        }
        self::$statistics[$stat->getLocalizedName()] = $stat;
    }

    /**
     * @param string $stat
     *
     * Unregisters the statistics.
     */
    public static function unregisterStatistic(string $stat): void
    {
        if(isset(self::$statistics[$stat]))
        {
            unset(self::$statistics[$stat]);
        }
    }

    /**
     * Initializes the form display statistic.
     */
    public static function init(): void
    {
        // Registers the kit name statistic to the form display.
        self::registerStatistic(new FormDisplayStatistic(
            self::STATISTIC_KIT_NAME,
            function (Player $player, Server $server, $data) {
                if ($data instanceof FFAArena) {
                    $kit = $data->getKit();
                } elseif ($data instanceof SavedKit) {
                    $kit = $data;
                }

                if (isset($kit)) {
                    $name = $kit !== null ? $kit->getName() : "None";
                    return $name;
                }

                return TextFormat::RED . "Unknown" . TextFormat::RESET;
            }
        ));
    }

    /**
     * @param string $message
     * @param Player $player
     * @param $args
     *
     * Convert the message based on parameters.
     */
    public static function convert(string &$message, Player $player, $args): void
    {
        foreach (self::$statistics as $localized => $statistic) {
            $statisticVariable = "{{$localized}}";
            if (strpos($message, $statisticVariable) !== 0) {
                $message = str_replace($statisticVariable, $statistic->getValue($player, $args), $message);
            }
        }
    }
}