<?php

declare(strict_types=1);

namespace jkorn\practice\items;


use jkorn\practice\games\misc\managers\IGameManager;
use pocketmine\item\Item;
use pocketmine\Player;

abstract class PracticeItemManager
{

    /** @var PracticeItem[] */
    protected $practiceItems = [];

    /** @var string */
    protected $resourcesFolder, $dataFolder;

    /** @var bool */
    private $loaded = false;

    public function __construct(string $resourcesFolder, string $dataFolder)
    {
        $this->resourcesFolder = $resourcesFolder;
        $this->dataFolder = $dataFolder;
    }

    /**
     * Loads the items in the item manager.
     */
    public function load(): void
    {
        if(!is_dir($this->dataFolder))
        {
            mkdir($this->dataFolder);
        }

        $this->onLoad();
        $this->loaded = true;
    }


    /**
     * @return void
     *
     * Loads the data from the input file.
     */
    abstract protected function onLoad(): void;

    /**
     * @param Item $item
     * @return PracticeItem|null - True if item is a callback item.
     *
     * Determines if the item is a callback item.
     */
    public function getPracticeItem(Item $item): ?PracticeItem
    {
        foreach($this->practiceItems as $practiceItem) {
            if($practiceItem->equals($item)) {
                return $practiceItem;
            }
        }
        return null;
    }

    /**
     * @param string $type - The type of item we want to send.
     * @param Player $player - The player we are sending the item to.
     * @param bool $clearInventory - Determines whether we should clear inventory
     *                              or not before sending the items.
     *
     * Sends the items based on the type.
     */
    abstract public function sendItemsFromType(string $type, Player $player, bool $clearInventory = true): void;

    /**
     * @return string
     *
     * Gets the data file of the item manager.
     */
    protected function getItemsFile(): string
    {
        return $this->dataFolder . "/items.yml";
    }
}