<?php

declare(strict_types=1);

namespace practice\items;


use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use practice\forms\display\FormDisplayManager;
use practice\misc\AbstractManager;
use practice\PracticeCore;

class ItemManager extends AbstractManager
{
    const ITEM_PLAY_FFA = "item.play.menu";
    const ITEM_PLAYER_SETTINGS = "item.player.settings";

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

        if (!file_exists($inputFile = $this->itemFolder . "/forms.yml")) {
            $resource = fopen($inputFile = $this->resourcesFolder . "/items.yml", "rb");
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
     * Saves the data from the manager, unused in the item
     * manager.
     *
     * @param bool $async
     */
    public function save(bool $async = false): void {}
}