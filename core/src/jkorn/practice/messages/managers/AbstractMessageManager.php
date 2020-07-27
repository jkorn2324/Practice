<?php

declare(strict_types=1);

namespace jkorn\practice\messages\managers;


use jkorn\practice\messages\PracticeMessage;
use pocketmine\Server;

abstract class AbstractMessageManager
{

    /** @var string */
    protected $resourcesFolder;
    /** @var string */
    protected $destinationFolder;

    /** @var Server */
    protected $server;

    /** @var bool */
    private $loaded = false;

    /** @var PracticeMessage[] */
    protected $messages = [];

    public function __construct(string $resourcesFolder, string $destinationFolder)
    {
        $this->resourcesFolder = $resourcesFolder;
        $this->destinationFolder = $destinationFolder;

        $this->server = Server::getInstance();
    }

    /**
     * Called when the message manager is registered.
     */
    abstract public function onRegister(): void;

    /**
     * @return string
     *
     * Gets the localized name of the abstract message manager.
     */
    abstract public function getLocalizedName(): string;

    /**
     * Loads the messages to the message list.
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
            file_exists($this->resourcesFolder . "/README.md")
            && !file_exists($mdFile = $this->destinationFolder . "/README.md")
        )
        {
            $mdResource = fopen($this->resourcesFolder . "/README.md", "rb");
            stream_copy_to_stream($mdResource, $file = fopen($mdFile, "wb"));
            fclose($mdResource);
            fclose($file);
        }

        // Creates a new scoreboards yaml file if it doesn't exist.
        if(
            file_exists($this->resourcesFolder . "/messages.yml")
            && !file_exists($inputFile = $this->destinationFolder . "/messages.yml")
        )
        {
            $yamlResources = fopen($this->resourcesFolder . "/messages.yml", "rb");
            stream_copy_to_stream($yamlResources, $file = fopen($inputFile, "wb"));
            fclose($yamlResources);
            fclose($file);
        }

        $this->loadMessages($inputFile);

        $this->loaded = true;
    }

    /**
     * @param string $inputFile
     *
     * Loads the messages from the message manager.
     */
    protected function loadMessages(string $inputFile): void
    {
        $data = yaml_parse_file($inputFile);

        foreach($data as $key => $message)
        {
            $message = new PracticeMessage($key, $message);
            $this->messages[$message->getLocalizedName()] = $message;
        }
    }

    /**
     * @return bool
     *
     * Determines whether or not the message manager is loaded.
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * @param string $message - The localized message.
     * @return PracticeMessage|null
     *
     * Gets the message from the localized name.
     */
    public function getMessage(string $message): ?PracticeMessage
    {
        if(isset($this->messages[$message]))
        {
            return $this->messages[$message];
        }
        return null;
    }
}