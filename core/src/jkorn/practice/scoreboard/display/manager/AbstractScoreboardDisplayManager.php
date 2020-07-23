<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-07-22
 * Time: 18:27
 */

declare(strict_types=1);

namespace jkorn\practice\scoreboard\display\manager;


use jkorn\practice\scoreboard\display\ScoreboardDisplayInformation;
use pocketmine\Server;

/**
 * Class AbstractScoreboardDisplayManager
 * @package jkorn\practice\scoreboard\display
 *
 * The base scoreboard display manager, used so that other plugins can
 * easily load in their own scoreboard managers.
 */
abstract class AbstractScoreboardDisplayManager
{

    /** @var ScoreboardDisplayInformation */
    protected $scoreboardDisplayInfo = [];
    /** @var string */
    protected $resourcesFolder, $destinationFolder;

    /** @var Server */
    protected $server;
    /** @var bool */
    private $loaded = false;

    public function __construct(string $resourcesFolder, string $destinationFolder)
    {
        $this->resourcesFolder = $resourcesFolder;
        $this->destinationFolder = $destinationFolder;

        $this->server = Server::getInstance();
    }

    /**
     * Called when the display manager is registered.
     */
    abstract public function onRegister(): void;

    /**
     * @return string
     *
     * Gets the localized name of the display manager, used
     * to distinguish from each other.
     */
    abstract public function getLocalized(): string;

    /**
     * @return bool
     *
     * Determines whether or not the display manager is loaded or not.
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * Loads the data needed for the manager.
     */
    public function load(): void
    {
        // Checks if the scoreboards folder exists.
        if(!is_dir($this->destinationFolder))
        {
            mkdir($this->destinationFolder);
        }

        // Creates the MD file.
        if(
            !file_exists($mdFile = $this->destinationFolder . "/README.md")
            && file_exists($this->resourcesFolder . "/README.md")
        )
        {
            $mdResource = fopen($this->resourcesFolder . "/README.md", "rb");
            stream_copy_to_stream($mdResource, $file = fopen($mdFile, "wb"));
            fclose($mdResource);
            fclose($file);
        }

        // Creates a new scoreboards yaml file if it doesn't exist.
        if(!file_exists($inputFile = $this->destinationFolder . "/scoreboards.yml"))
        {
            $yamlResources = fopen($this->resourcesFolder . "/scoreboards.yml", "rb");
            stream_copy_to_stream($yamlResources, $file = fopen($inputFile, "wb"));
            fclose($yamlResources);
            fclose($file);
        }

        $this->loadScoreboardDisplays($inputFile);

        $this->loaded = true;
    }

    /**
     * @param string $file - The file name where to load it.
     *
     * Loads the scoreboard display based on the file.
     */
    protected function loadScoreboardDisplays(string $file): void
    {
        $data = yaml_parse_file($file);

        foreach($data as $key => $scoreboardDisplayInformation)
        {
            $scoreboardDisplay = new ScoreboardDisplayInformation($key, $scoreboardDisplayInformation);
            $this->scoreboardDisplayInfo[$key] = $scoreboardDisplay;
        }
    }


    /**
     * @param string $key
     * @return ScoreboardDisplayInformation|null
     *
     * Gets the display information based on the key.
     */
    public function getDisplayInfo(string $key): ?ScoreboardDisplayInformation
    {
        if(isset($this->scoreboardDisplayInfo[$key]))
        {
            return $this->scoreboardDisplayInfo[$key];
        }

        return null;
    }
}