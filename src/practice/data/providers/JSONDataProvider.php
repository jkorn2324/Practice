<?php

declare(strict_types=1);

namespace practice\data\providers;


use pocketmine\Player;
use pocketmine\Server;
use practice\data\IDataProvider;
use practice\misc\PracticeAsyncTask;
use practice\player\PracticePlayer;
use practice\PracticeCore;
use practice\PracticeUtil;

/**
 * The class that is used for json data storage.
 *
 * @package practice\data\providers
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
                    // TODO: Implement onRun() method.
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
}