<?php

declare(strict_types=1);

namespace jkorn\practice\data\providers;


use jkorn\practice\games\IGameManager;
use jkorn\practice\games\misc\leaderboards\LeaderboardGroup;
use pocketmine\Player;
use pocketmine\Server;
use jkorn\practice\data\IDataProvider;
use jkorn\practice\misc\PracticeAsyncTask;
use jkorn\practice\player\PracticePlayer;
use jkorn\practice\PracticeCore;
use jkorn\practice\PracticeUtil;

/**
 * The class that is used for json data storage.
 *
 * @package jkorn\practice\data\providers
 */
class JSONDataProvider implements IDataProvider
{

    // Determines if current provider is enabled.
    const ENABLED = true;

    /** @var string */
    private $dataFolder;
    /** @var Server */
    private $server;

    public function __construct()
    {
        $this->dataFolder = PracticeCore::getInstance()->getDataFolder();
        $this->server = Server::getInstance();
    }

    /**
     * @param Player $player
     *
     * Loads the player's data.
     */
    public function loadPlayer(Player $player): void
    {
        if(!self::ENABLED || !$player instanceof PracticePlayer)
        {
            return;
        }

        $this->server->getAsyncPool()->submitTask(new class($player, $this->dataFolder) extends PracticeAsyncTask
        {

            private const DATA_FAILED_ERROR = 0;
            private const DATA_FAILED_FILE_NOT_FOUND = 1;
            private const DATA_FAILED_NEW_PLAYER = 2;

            /** @var string */
            private $name;
            /** @var string */
            private $serverUUID;
            /** @var string */
            private $playersDirectory;
            /** @var string */
            private $jsonFile;
            /** @var bool */
            private $firstJoin;

            public function __construct(PracticePlayer $player, string $dataFolder)
            {
                $this->name = $player->getName();
                $this->serverUUID = $player->getServerID()->toString();
                $this->playersDirectory = $dataFolder . "players/";
                $this->jsonFile = $this->playersDirectory . $player->getName() . ".json";
                $this->firstJoin = !$player->hasPlayedBefore();
            }

            /**
             * Actions to execute when run
             *
             * @return void
             */
            public function onRun()
            {
                if(!is_dir($this->playersDirectory))
                {
                    mkdir($this->playersDirectory);
                }

                if(!file_exists($this->jsonFile)) {

                    $reason = $this->firstJoin ? self::DATA_FAILED_NEW_PLAYER : self::DATA_FAILED_FILE_NOT_FOUND;
                    $file = fopen($this->jsonFile, "w");
                    fclose($file);
                    $this->setResult(["loaded" => false, "reason" => $reason]);
                    return;
                }

                try
                {
                    $contents = json_decode(file_get_contents($this->jsonFile), true);
                    $this->setResult(["loaded" => true, "contents" => $contents]);

                } catch (\Exception $e)
                {
                    $this->setResult(["loaded" => false, "reason" => self::DATA_FAILED_ERROR, "exception" => $e->getTraceAsString()]);
                }
            }

            /**
             * Called in the onCompletion function
             * & used for tasks running in there.
             *
             * @param Server $server
             */
            protected function doComplete(Server $server): void
            {
                $result = $this->getResult();
                $player = PracticeUtil::getPlayerFromServerID($this->serverUUID);

                if($player === null || !$player instanceof PracticePlayer)
                {
                    if(isset($result["exception"]))
                    {
                        // Alerts the server to the exception.
                        $server->getLogger()->alert($result["exception"]);
                    }
                    return;
                }

                $loaded = (bool)$result["loaded"];

                if(!$loaded)
                {
                    if(isset($result["reason"]))
                    {
                        $reason = (int)$result["reason"];
                        if($reason !== self::DATA_FAILED_NEW_PLAYER)
                        {
                            if($reason === self::DATA_FAILED_ERROR)
                            {
                                $player->setSaveData(false);
                            }

                            $player->sendMessage("Unable to load your data. Reason: Internal Plugin Error.");
                        }
                    }

                    if(isset($result["exception"]))
                    {
                        $server->getLogger()->alert($result["exception"]);
                    }
                    $player->loadData(null);
                    return;
                }

                // Sends the contents for the player to load.
                $data = $result["contents"];
                $player->loadData($data);
            }

        });
    }

    /**
     * @param Player $player
     * @param bool $async - Async is done when server isn't shutting down.
     *
     * Saves the player's data.
     */
    public function savePlayer(Player $player, bool $async): void
    {
        if(!self::ENABLED || !$player instanceof PracticePlayer)
        {
            return;
        }

        // Determines whether to save the player's data.
        if(!$player->doSaveData() || $player->isSaved())
        {
            return;
        }

        // Sets the player as saved.
        $player->setSaved(true);

        if($async) {

            $this->server->getAsyncPool()->submitTask(new class($player, $this->dataFolder) extends PracticeAsyncTask
            {

                /** @var string */
                private $jsonFile;
                /** @var string */
                private $exportedData;

                public function __construct(PracticePlayer $player, string $dataFolder)
                {
                    $this->jsonFile = $dataFolder . "players/{$player->getName()}.json";
                    $this->exportedData = json_encode($player->exportData());
                }

                /**
                 * Actions to execute when run
                 *
                 * @return void
                 */
                public function onRun()
                {
                    if(!file_exists($this->jsonFile))
                    {
                        $file = fopen($this->jsonFile, "w");
                        fclose($file);
                    }

                    file_put_contents($this->jsonFile, $this->exportedData);
                }

                /**
                 * Called in the onCompletion function
                 * & used for tasks running in there.
                 *
                 * @param Server $server
                 */
                protected function doComplete(Server $server): void {}
            });
            return;
        }

        // Runs non-async.

        $jsonFile = $this->dataFolder . "players/{$player->getName()}.json";
        $exportedData = json_encode($player->exportData());

        if(!file_exists($jsonFile))
        {
            $file = fopen($jsonFile, "w");
            fclose($file);
        }

        file_put_contents($jsonFile, $exportedData);
    }

    /**
     * Saves all the player's data on the server, used when the server shuts down.
     */
    public function saveAllPlayers(): void
    {
        $players = $this->server->getOnlinePlayers();
        foreach($players as $player)
        {
            $this->savePlayer($player, false);
        }
    }

    /**
     * @param IGameManager $gameType - The game type.
     * @param LeaderboardGroup[] $leaderboardGroups
     *
     * Updates the leaderboards based on the input leaderboard groups and the game type,
     * is always run under async tasks.
     */
    public function updateLeaderboards(IGameManager $gameType, $leaderboardGroups): void
    {
        if(!self::ENABLED)
        {
            return;
        }

        $players = $this->server->getOnlinePlayers();
        $leaderboardGroups = array_filter($leaderboardGroups, function(LeaderboardGroup $group)
        {
            return $group->doLoad();
        });

        // Initializes the leaderboard groups.
        $inputLeaderboardGroups = [];
        foreach($leaderboardGroups as $group)
        {
            $inputStatistics = [];
            foreach($players as $player)
            {
                if($player instanceof PracticePlayer)
                {
                    $statistic = $player->getStatsInfo()->getStatistic($group->getStatistic());
                    if($statistic !== null)
                    {
                        $inputStatistics[$player->getName()] = $statistic->getValue();
                    }
                    else
                    {
                        $inputStatistics[$player->getName()] = 0;
                    }
                }
            }
            $inputLeaderboardGroups[$group->getStatistic()] = $inputStatistics;
        }


        $this->server->getAsyncPool()->submitTask(new class($leaderboardGroups, $this->dataFolder, $gameType->getType()) extends PracticeAsyncTask
        {

            /** @var string */
            private $leaderboardGroups;
            /** @var string */
            private $gameType;
            /** @var string */
            private $playersFolder;

            /**
             * The async class constructor.
             * @param array $inputStatistics
             * @param string $gameType
             * @param LeaderboardGroup[] $leaderboardGroups
             */
            public function __construct(array $inputStatistics, string $dataFolder, string $gameType)
            {
                $this->gameType = $gameType;
                $this->leaderboardGroups = json_encode($inputStatistics);
                $this->playersFolder = $dataFolder . "players/";
            }


            /**
             * Actions to execute when run.
             *
             * @return void
             */
            public function onRun()
            {
                $leaderboardGroups = json_decode($this->leaderboardGroups, true);
                if(!is_dir($this->playersFolder))
                {
                    mkdir($this->playersFolder);
                    $this->setResult(["leaderboards" => $leaderboardGroups]);
                    return;
                }

                $players = scandir($this->playersFolder);

                foreach($players as $file)
                {
                    if(strpos($file, ".json") !== false)
                    {
                        $filePath = $this->playersFolder . $file;
                        $decodedData = json_decode(file_get_contents($filePath), true);
                        if(isset($decodedData["stats"]))
                        {
                            $stats = $decodedData["stats"];
                            $player = str_replace(".json", "", $file);
                            $this->addStatsToGroups($player, $stats, $leaderboardGroups);
                        }
                    }
                }

                $this->reorganize($leaderboardGroups);
                $this->setResult(["leaderboards" => $leaderboardGroups]);
            }

            /**
             * @param string $player - The player name.
             * @param $stats - The player's statistics.
             * @param $leaderboardGroups - The leaderboard groups containing the statistics.
             *
             * Adds the statistic to a given leaderboard group.
             */
            private function addStatsToGroups(string &$player, &$stats, &$leaderboardGroups): void
            {
                $statistics = array_keys($leaderboardGroups);

                for($i = 0; $i < count($leaderboardGroups); $i++) {
                    // The statistic.
                    $statistic = $statistics[$i];
                    // The data of the leaderboards containing the players.
                    $data = $leaderboardGroups[$statistic];

                    if (isset($data[$player])) {
                        continue;
                    }

                    if (isset($stats[$statistic])) {
                        $playerStat = $stats[$statistic];
                        $data[$player] = $playerStat;
                    }
                    $leaderboardGroups[$statistic] = $data;
                }
            }

            /**
             * @param array $groups
             *
             * Reorganizes the leaderboard groups and orders them.
             */
            private function reorganize(array &$groups): void
            {
                $statistics = array_keys($groups);

                for($i = 0; $i < count($groups); $i++) {
                    $statistic = $statistics[$i];
                    asort($groups[$statistic]);
                }
            }


            /**
             * Called in the onCompletion function
             * & used for tasks running in there.
             *
             * @param Server $server
             */
            protected function doComplete(Server $server): void
            {
                $gameManager = PracticeCore::getBaseGameManager()->getGameManager($this->gameType);
                if($gameManager !== null)
                {
                    $leaderboard = $gameManager->getLeaderboard();
                    if($leaderboard !== null)
                    {
                        $leaderboards = $this->getResult()["leaderboards"];
                        $leaderboard->finishUpdate($leaderboards);
                    }
                }
            }
        });
    }
}