<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-08-05
 * Time: 12:30
 */

declare(strict_types=1);

namespace old\practice\player;


use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use old\practice\PracticeCore;

class AsyncUpdateLeaderboards extends AsyncTask
{

    /* @var string */
    private $playerFolderPath;

    private $mysqlEnabled;

    private $kits;

    public function __construct(string $playerFolderPath, bool $mysqlEnabled, array $kits)
    {
        $this->playerFolderPath = $playerFolderPath;
        $this->mysqlEnabled = $mysqlEnabled;
        $this->kits = $kits;
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {
        foreach ($this->kits as $name) {

            $leaderboard = $this->getLeaderboardsFrom($name);

            if (is_bool($leaderboard)) {
                $this->setResult($leaderboard);
                return;
            }

            $result[$name] = $leaderboard;
        }

        $global = $this->getLeaderboardsFrom();

        $result['global'] = $global;

        $this->setResult($result);
    }

    public function onCompletion(Server $server)
    {
        $plugin = $server->getPluginManager()->getPlugin('Practice');

        if ($plugin !== null and $plugin instanceof PracticeCore) {
            $result = $this->getResult();

            if (is_bool($result)) {
                PracticeCore::getPlayerHandler()->updateLeaderboards();
            } else PracticeCore::getPlayerHandler()->setLeaderboards($result);
        }
    }

    /**
     * @param string $queue
     * @return string[]|bool
     */
    public function getLeaderboardsFrom(string $queue = 'global')
    {

        $result = [];

        $format = "\n" . TextFormat::GRAY . '%spot%. ' . TextFormat::AQUA . '%player% ' . TextFormat::WHITE . '(%elo%)';

        if ($this->mysqlEnabled === false) {

            $sortedElo = $this->listEloForAll($queue);

            $playerNames = array_keys($sortedElo);

            $size = count($sortedElo) - 1;

            $subtracted = ($size > 10) ? 9 : $size;

            $len = $size - $subtracted;

            for ($i = $size; $i >= $len; $i--) {
                $place = $size - $i;
                $name = strval($playerNames[$i]);
                $elo = intval($sortedElo[$name]);
                $string = str_replace('%spot%', $place + 1, str_replace('%player%', $name, str_replace('%elo%', $elo, $format)));
                //$string = PracticeUtil::str_replace($format, ['%spot%' => $place + 1, '%player%' => $name, '%elo%' => $elo]);
                $result[] = $string;
            }

            $size = count($result);

            if ($size > 10) {
                for ($i = $size; $i > 9; $i--) {
                    if (isset($result[$i]))
                        unset($result[$i]);
                }
            }

        } else {
            $result = false;
        }

        return $result;
    }

    private function listEloForAll(string $queue): array
    {

        $player_array = [];

        if (is_dir($this->playerFolderPath)) {

            $files = scandir($this->playerFolderPath);

            foreach ($files as $file) {

                if (strpos($file, '.yml') !== false) {


                    $name = strval(str_replace('.yml', '', $file));

                    $path = $this->playerFolderPath . "/$name.yml";

                    $stats = yaml_parse_file($path, 0)['stats'];

                    $elo = $stats['elo'];

                    $resElo = 0;

                    if ($queue === 'global') {

                        $total = 0;

                        $count = count($elo);

                        $keys = array_keys($elo);

                        foreach ($keys as $q)
                            $total += intval($elo[$q]);

                        $resElo = ($count !== 0) ? intval($total / $count) : 1000;

                    } else {

                        if (isset($elo[$queue]))
                            $resElo = intval($elo[$queue]);
                    }

                    $player_array[$name] = $resElo;
                }
            }
        }

        asort($player_array);

        return $player_array;
    }
}