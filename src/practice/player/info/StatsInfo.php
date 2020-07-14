<?php

declare(strict_types=1);

namespace practice\player\info;


use practice\misc\ISavedHeader;

class StatsInfo implements ISavedHeader
{

    /**
     * Determine whether or not to use elo or a new way to collect
     * duel wins and losses.
     */

    /** @var int */
    private $kills = 0, $deaths = 0;

    public function __construct(int $kills = 0, int $deaths = 0)
    {
        $this->kills = $kills;
        $this->deaths = $deaths;
    }

    /**
     * Adds a kill to the player.
     */
    public function addKill(): void
    {
        ++$this->kills;
    }

    /**
     * @return int
     *
     * Gets the kills of the player.
     */
    public function getKills(): int
    {
        return $this->kills;
    }

    /**
     * Adds deaths to the statistics information.
     */
    public function addDeath(): void
    {
        ++$this->deaths;
    }

    /**
     * @return int
     *
     * Gets the deaths information.
     */
    public function getDeaths(): int
    {
        return $this->deaths;
    }

    /**
     * @return array
     *
     * Exports the player's statistics information.
     */
    public function export(): array
    {
        return [
            "kills" => $this->kills,
            "deaths" => $this->deaths
        ];
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
        if(!is_array($data) || !isset($data[$header = "stats"]))
        {
            $statsInfo = new StatsInfo();
            return;
        }

        $stats = $data[$header];
        $kills = 0; $deaths = 0;

        if(isset($stats["kills"]))
        {
            $kills = (int)$stats["kills"];
        }

        if(isset($stats["deaths"]))
        {
            $deaths = (int)$stats["deaths"];
        }

        if($statsInfo instanceof StatsInfo)
        {
            $statsInfo->deaths = $deaths;
            $statsInfo->kills = $kills;
            return;
        }

        $statsInfo = new StatsInfo($kills, $deaths);
    }
}