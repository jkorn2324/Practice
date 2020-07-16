<?php

declare(strict_types=1);

namespace jkorn\practice\items;


use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use jkorn\practice\forms\display\FormDisplayManager;
use jkorn\practice\misc\AbstractManager;
use jkorn\practice\PracticeCore;

class ItemManager extends AbstractManager
{
    const ITEM_PLAY_FFA = "item.play.menu";
    const ITEM_PLAYER_SETTINGS = "item.player.settings";
    const ITEM_QUEUE_LEAVE = "item.queue.leave";

    const TYPE_LOBBY = "type.lobby";

    /** @var array|PracticeItem[] */
    private $items;

    /** @var string */
    private $resourcesFolder, $itemFolder;

    public function __construct(PracticeCore $core)
    {
        $this->resourcesFolder = $core->getResourcesFolder() . "items";
        $this->itemFolder = $core->getDataFolder() . "items";

        $this->items = [];

        parent::__construct($core, false);
    }

    /**
     * Loads the data needed for the manager.
     *
     * @param bool $async
     */
    protected function load(bool $async = false): void
    {
        if (!is_dir($this->itemFolder)) {
            mkdir($this->itemFolder);
        }

        if (!file_exists($mdFile = $this->itemFolder . "/README.md")) {
            $mdResource = fopen($this->resourcesFolder . "/README.md", "rb");
            stream_copy_to_stream($mdResource, $file = fopen($mdFile, "wb"));
            fclose($mdResource);
            fclose($file);
        }

        if (!file_exists($inputFile = $this->itemFolder . "/items.yml")) {
            $resource = fopen($this->resourcesFolder . "/items.yml", "rb");
            stream_copy_to_stream($resource, $file = fopen($inputFile, "wb"));
            fclose($resource);
            fclose($file);
        }

        $this->loadItems($inputFile);
    }

    /**
     * @param string $inputFile
     *
     * Loads the items from the input file.
     */
    private function loadItems(string $inputFile): void
    {
        $itemsData = yaml_parse_file($inputFile);
        foreach ($itemsData as $localizedName => $data) {
            $item = PracticeItem::decode($localizedName, $data);
            if ($item !== null) {
                $this->items[$item->getLocalized()] = $item;
            }
        }

        $this->updateCallbacks();
    }

    /**
     * Updates the callbacks of the items.
     */
    private function updateCallbacks(): void
    {
        // This edits the callable of the ffa item.
        if (isset($this->items[self::ITEM_PLAY_FFA])) {
            $this->items[self::ITEM_PLAY_FFA]->setCallable(function (Player $player) {

                // Sends the play form to the player.
                $theForm = PracticeCore::getFormDisplayManager()->getForm(FormDisplayManager::FORM_PLAY_FFA);
                if ($theForm !== null) {
                    $theForm->display($player);
                } else {
                    $player->sendMessage(TextFormat::RED . "[ERROR-PRACTICE] Internal plugin error, unable to show the play form.");
                }
                return true;
            });
        }

        // This edits the callback of the settings item.
        if (isset($this->items[self::ITEM_PLAYER_SETTINGS])) {
            $this->items[self::ITEM_PLAYER_SETTINGS]->setCallable(function (Player $player) {

                $theForm = PracticeCore::getFormDisplayManager()->getForm(FormDisplayManager::FORM_SETTINGS_MENU);
                if ($theForm !== null) {
                    $theForm->display($player);
                } else {
                    $player->sendMessage(TextFormat::RED . "[ERROR-PRACTICE] Internal plugin error, unable to show the setting menu form.");
                }
                return true;
            });
        }

        if(isset($this->items[self::ITEM_QUEUE_LEAVE])) {
            $this->items[self::ITEM_QUEUE_LEAVE]->setCallable(function (Player $player) {
                // TODO
            });
        }
    }

    /**
     * @param Item $item
     * @return PracticeItem|null - True if item is a callback item.
     *
     * Determines if the item is a callback item.
     */
    public function getPracticeItem(Item $item): ?PracticeItem
    {
        foreach($this->items as $practiceItem)
        {
            if($practiceItem->equals($item))
            {
                return $practiceItem;
            }
        }

        return null;
    }

    /**
     * @param Player $player
     * @param PracticeItem|null $item
     *
     * Sends an item to the player.
     */
    public function sendItem(Player $player, ?PracticeItem $item): void
    {
        if($item === null)
        {
            return;
        }

        $player->getInventory()->setItem($item->getSlot(), $item->getItem());
    }

    /**
     * @param string $type
     * @param Player $player
     * @param bool $clearInventory
     *
     * Sends the items based on the given type [EX: TYPE_LOBBY]
     */
    public function sendItemsFromType(string $type, Player $player, bool $clearInventory = true): void
    {
        if($clearInventory)
        {
            $player->getInventory()->clearAll();
            $player->getArmorInventory()->clearAll();
        }

        // TODO: Implement fully.
        switch($type)
        {
            case self::TYPE_LOBBY:
                $this->sendItem($player, $this->items[self::ITEM_PLAY_FFA]);
                $this->sendItem($player, $this->items[self::ITEM_PLAYER_SETTINGS]);
                break;
        }
    }

    /**
     * Saves the data from the manager, unused in the item
     * manager.
     *
     * @param bool $async
     */
    public function save(bool $async = false): void {}
}