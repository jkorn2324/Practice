<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-06-16
 * Time: 12:29
 */

namespace practice\manager\tasks;


use pocketmine\scheduler\AsyncTask;
use practice\ranks\RankHandler;

class CreatePDataTask extends AsyncTask
{

    private $encodedIp;
    private $playerName;

    /* @var array */
    private $kits;

    private $path;
    private $guestRank;

    public function __construct(string $player, string $path, string $guestRank, string $encodedIp, array $kits)
    {
        $this->path = $path . "/$player.yml";
        $this->playerName = $player;
        $this->encodedIp = $encodedIp;
        $this->guestRank = $guestRank;
        $this->kits = $kits;
    }

    private function isDuelKit(string $kit) : bool {
        return in_array($kit, $this->kits, FALSE);
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {
        if (!file_exists($this->path)) {

            $file = fopen($this->path, 'wb');

            fclose($file);

            $elo = [];

            $size = count($this->kits);

            if ($size > 0) {
                foreach ($this->kits as $kit) {
                    $name = strval($kit);
                    $elo[$name] = 1000;
                }
            }

            $data = array(
                'aliases' => [$this->playerName],
                'stats' => array(
                    'kills' => 0,
                    'deaths' => 0,
                    'elo' => $elo
                ),
                'muted' => false,
                'ranks' => array(
                    $this->guestRank
                ),
                'scoreboards-enabled' => true,
                'place-break' => false,
                'pe-only' => false,
                'ips' => [$this->encodedIp]
            );

            yaml_emit_file($this->path, $data);

        } else {

            $data = yaml_parse_file($this->path);

            $emit = false;

            if (!isset($data['scoreboards-enabled'])) {
                $data['scoreboards-enabled'] = true;
                $emit = true;
            }

            if (!isset($data['place-break'])) {
                $data['place-break'] = false;
                $emit = true;
            }

            if (!isset($data['pe-only'])) {
                $data['pe-only'] = false;
                $emit = true;
            }

            if (!isset($data['ips'])) {
                $data['ips'] = [$this->encodedIp];
                $emit = true;
            }

            $stats = $data['stats'];

            $elo = $stats['elo'];

            $keys = array_keys($elo);

            sort($keys);

            $kits = (array)$this->kits;

            sort($kits);

            if ($keys !== $kits) {

                $difference = array_diff($kits, $keys);

                foreach ($difference as $kit) {

                    if ($this->isDuelKit($kit))
                        $elo[$kit] = 1000;
                    else {
                        if (isset($elo[$kit]))
                            unset($elo[$kit]);
                    }
                }

                $stats['elo'] = $elo;

                $data['stats'] = $stats;

                $emit = true;
            }

            if ($emit === true) yaml_emit_file($this->path, $data);
        }
    }


}