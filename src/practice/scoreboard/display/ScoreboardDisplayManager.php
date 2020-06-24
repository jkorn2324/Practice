<?php

declare(strict_types=1);

namespace practice\scoreboard;


use practice\misc\AbstractManager;
use practice\PracticeCore;

class ScoreboardDisplayManager extends AbstractManager
{


    /** @var ScoreboardDisplayInformation */
    private $scoreboardDisplayInfo = [];
    /** @var string */
    private $resourcesScoreboardFolder, $scoreboardsFolder;

    public function __construct(PracticeCore $core)
    {
        $this->resourcesScoreboardFolder = $core->getResourcesFolder() . "scoreboards";
        $this->scoreboardsFolder = $core->getDataFolder() . "scoreboards";

        parent::__construct($core);
    }

    /**
     * Loads the data needed for the manager.
     *
     * @param bool $async - Unused here.
     */
    protected function load(bool $async = false): void
    {
        // Checks if the scoreboards folder exists.
        if(!is_dir($this->scoreboardsFolder))
        {
            mkdir($this->scoreboardsFolder);
        }

        // Creates the MD file.
        if(!file_exists($mdFile = $this->scoreboardsFolder . "/README.md"))
        {
            $mdResource = fopen($this->resourcesScoreboardFolder . "/README.md", "rb");
            stream_copy_to_stream($mdResource, $file = fopen($mdFile, "wb"));
            fclose($mdResource);
            fclose($file);
        }

        // Creates a new scoreboards yaml file if it doesn't exist.
        if(!file_exists($inputFile = $this->scoreboardsFolder . "/scoreboards.yml"))
        {
            $yamlResources = fopen($inputFile = $this->resourcesScoreboardFolder . "/scoreboards.yml", "rb");
            stream_copy_to_stream($yamlResources, $file = fopen($yamlResources, "wb"));
            fclose($yamlResources);
            fclose($file);
        }

        $this->loadScoreboardDisplays($inputFile);
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

    /**
     * Saves the data from the manager, do nothing for this manager.
     * @param bool $async
     */
    public function save(bool $async = false): void {}
}