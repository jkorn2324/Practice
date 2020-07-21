<?php

declare(strict_types=1);

namespace jkorn\practice\player\info\stats;


use jkorn\practice\misc\ISavedHeader;
use jkorn\practice\player\info\stats\properties\IntegerStatProperty;

class StatsInfo implements ISavedHeader
{

    const STAT_KILLS = "stat.kills";
    const STAT_DEATHS = "stat.deaths";

    /** @var StatPropertyInfo[] */
    private static $statistics = [];

    /**
     * Initializes the default statistics.
     */
    public static function initDefaultStats(): void
    {
        self::registerStatistic(new StatPropertyInfo(self::STAT_DEATHS, IntegerStatProperty::class, true));
        self::registerStatistic(new StatPropertyInfo(self::STAT_KILLS, IntegerStatProperty::class, true));
    }

    /**
     * @param StatPropertyInfo $info
     * @param bool $override
     *
     * Registers the statistic to the statistics list.
     */
    public static function registerStatistic(StatPropertyInfo $info, bool $override = true): void
    {
        if(isset(self::$statistics[$info->getName()]) && !$override)
        {
            return;
        }

        self::$statistics[$info->getName()] = $info;
    }

    /**
     * @param string $name
     *
     * Unregisters the statistic.
     */
    public static function unregisterStatistic(string $name): void
    {
        if(isset(self::$statistics[$name]))
        {
            unset(self::$statistics[$name]);
        }
    }

    /**
     * @return IStatProperty[]
     *
     * Gets the statistics as properties.
     */
    private static function getProperties()
    {
        $output = [];
        foreach(self::$statistics as $statistic)
        {
            $output[$statistic->getName()] = $statistic->convertToInstance();
        }
        return $output;
    }

    // ------------------------------------- Statistic Information -----------------------------------

    /** @var IStatProperty[] */
    private $properties = [];

    public function __construct()
    {
        $this->properties = self::getProperties();
    }

    /**
     * @param string $name
     * @return IStatProperty|null
     *
     * Gets the statistic from the properties list.
     */
    public function getStatistic(string $name): ?IStatProperty
    {
        if(isset($this->properties[$name]))
        {
            return $this->properties[$name];
        }
        return null;
    }

    /**
     * @return array
     *
     * Exports the player's statistics information.
     */
    public function export(): array
    {
        $output = [];
        foreach($this->properties as $property)
        {
            if($property->doSave())
            {
                $output[$property->getLocalized()] = $property->getValue();
            }
        }
        return $output;
    }

    /**
     * @return string
     *
     * Gets the stats information header.
     */
    public function getHeader()
    {
        return "stats";
    }

    /**
     * @param $data - The data.
     * @param $statsInfo - The stats information.
     *
     * Extracts the data from the info & initializes the statistics.
     */
    public static function extract(&$data, &$statsInfo): void
    {
        if(!$statsInfo instanceof StatsInfo)
        {
            $statsInfo = new StatsInfo();
            self::extract($data, $statsInfo);
            return;
        }

        if(!is_array($data) || !isset($data["stats"]))
        {
            return;
        }

        $stats = $data["stats"];
        foreach($statsInfo->properties as $propertyName => $property)
        {
            if(isset($stats[$propertyName]))
            {
                $property->setValue($stats[$propertyName]);
            }
        }
    }
}