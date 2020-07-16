<?php

declare(strict_types=1);

namespace jkorn\practice\data\providers;

use pocketmine\Player;
use pocketmine\Server;
use jkorn\practice\data\IDataProvider;
use jkorn\practice\misc\PracticeAsyncTask;
use jkorn\practice\player\PracticePlayer;
use jkorn\practice\PracticeCore;
use jkorn\practice\PracticeUtil;

/**
 * Class YAMLDataProvider
 * @package jkorn\practice\data\providers
 *
 * The data provider that handles yaml format.
 */
class YAMLDataProvider implements IDataProvider
{

    // Determines if this data provider is enabled.
    const ENABLED = true;

    /** @var Server */
    private $server;
    /** @var string */
    private $dataFolder;

    public function __construct()
    {
        $this->server = Server::getInstance();
        $this->dataFolder = PracticeCore::getInstance()->getDataFolder();
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
            private $yamlFile;
            /** @var string */
            private $directory;
            /** @var bool */
            private $firstJoin;
            /** @var string */
            private $serverUUID;

            public function __construct(PracticePlayer $player, string $dataFolder)
            {
                $this->directory = $dataFolder . "players/";
                $this->name = $player->getName();
                $this->yamlFile = $this->directory . "{$this->name}.yml";
                $this->firstJoin = !$player->hasPlayedBefore();
                $this->serverUUID = $player->getServerID()->toString();
            }

            /**
             * Actions to execute when run
             *
             * @return void
             */
            public function onRun()
            {
                if(!is_dir($this->directory))
                {
                    mkdir($this->directory);
                }

                if(!file_exists($this->yamlFile))
                {
                    $reason = $this->firstJoin ? self::DATA_FAILED_NEW_PLAYER : self::DATA_FAILED_FILE_NOT_FOUND;
                    $file = fopen($this->yamlFile, "w");
                    fclose($file);
                    $this->setResult(["loaded" => false, "reason" => $reason]);
                    return;
                }

                try
                {
                    $data = yaml_parse_file($this->yamlFile);
                    $this->setResult(["loaded" => true, "contents" => $data]);

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
     * @param bool $async - Determines whether to save async or not.
     *
     * Saves the player's data.
     */
    public function savePlayer(Player $player, bool $async): void
    {
        if(!self::ENABLED || !$player instanceof PracticePlayer)
        {
            return;
        }

        if(!$player->doSaveData() || $player->isSaved())
        {
            return;
        }

        // Sets the player as saved.
        $player->setSaved(true);

        if($async)
        {
            $this->server->getAsyncPool()->submitTask(new class($player, $this->dataFolder) extends PracticeAsyncTask
            {

                /** @var string */
                private $yamlFile;
                /** @var string */
                private $data;

                public function __construct(PracticePlayer $player, string $dataFolder)
                {
                    $this->yamlFile = $dataFolder . "players/" . $player->getName() . ".yml";
                    $this->data = json_encode($player->exportData());
                }

                /**
                 * Actions to execute when run
                 *
                 * @return void
                 */
                public function onRun()
                {
                    $data = json_decode($this->data, true);

                    if(!file_exists($this->yamlFile))
                    {
                        $file = fopen($this->yamlFile, "w");
                        fclose($file);
                    }

                    yaml_emit_file($this->yamlFile, $data);
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

        $yamlFile = $this->dataFolder . "players/" . $player->getName() . ".yml";
        $exportedData = $player->exportData();

        if(!file_exists($yamlFile))
        {
            $file = fopen($yamlFile, "w");
            fclose($file);
        }

        yaml_emit_file($yamlFile, $exportedData);
    }

    /**
     * Saves the data of all the players, used for when the server shuts down.
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