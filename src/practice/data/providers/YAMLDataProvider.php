<?php

declare(strict_types=1);

namespace practice\data\providers;


use mysql_xdevapi\Exception;
use pocketmine\Player;
use pocketmine\Server;
use practice\data\IDataProvider;
use practice\misc\PracticeAsyncTask;
use practice\player\PracticePlayer;
use practice\PracticeCore;
use practice\PracticeUtil;

/**
 * Class YAMLDataProvider
 * @package practice\data\providers
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
                    $reason = $this->firstJoin ? "new" : "Internal Plugin Error.";
                    $file = fopen($this->yamlFile, "w");
                    fclose($file);
                    $this->setResult(["loaded" => false, "reason" => $reason]);
                    return;
                }

                try
                {
                    $data = yaml_parse_file($this->yamlFile);
                    $this->setResult(["loaded" => true, "contents" => $data]);

                } catch (Exception $e)
                {
                    $this->setResult(["loaded" => false, "reason" => "Internal Plugin Error.", "exception" => $e->getTraceAsString()]);
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
                    if(isset($result["reason"]) && $result["reason"] !== "new")
                    {
                        // TODO: Send unable to load reason.
                        $player->sendMessage("Unable to load your data. Reason: " . $result["reason"]);
                        $player->setSaveData(false);
                    }

                    if(isset($result["exception"]))
                    {
                        $server->getLogger()->alert($result["exception"]);
                    }
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

        if(!$player->doSaveData())
        {
            return;
        }

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
}