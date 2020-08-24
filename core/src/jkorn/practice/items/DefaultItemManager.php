<?php

declare(strict_types=1);

namespace jkorn\practice\items;


use jkorn\practice\forms\display\manager\PracticeFormManager;
use jkorn\practice\PracticeCore;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class DefaultItemManager extends PracticeItemManager
{

    const ITEM_PLAY_GAMES = "item.play.games";
    const ITEM_PLAYER_SETTINGS = "item.player.settings";

    const TYPE_LOBBY = "type.lobby";

    /** @var PracticeCore */
    private $core;

    public function __construct(PracticeCore $core)
    {
        $this->core = $core;

        parent::__construct($core->getResourcesFolder() . "items", $core->getDataFolder() . "items");

        // Loads the item manager by default.
        $this->load();
    }

    /**
     * @return void
     *
     * Loads the data from the input file.
     */
    protected function onLoad(): void
    {
        // Copies the data from resource read me to the data yaml.
        if (!file_exists($mdFile = $this->dataFolder . "/README.md")) {
            $mdResource = fopen($this->resourcesFolder . "/README.md", "rb");
            stream_copy_to_stream($mdResource, $file = fopen($mdFile, "wb"));
            fclose($mdResource);
            fclose($file);
        }

        // Copies the data from resource yaml to the data folder yaml.
        if (!file_exists($this->getItemsFile())) {
            $resource = fopen($this->resourcesFolder . "/items.yml", "rb");
            stream_copy_to_stream($resource, $file = fopen($this->getItemsFile(), "wb"));
            fclose($resource);
            fclose($file);
        }

        $itemsData = yaml_parse_file($this->getItemsFile());
        foreach ($itemsData as $localizedName => $data) {
            $item = PracticeItem::decode($localizedName, $data);
            if ($item !== null) {
                $this->practiceItems[$item->getLocalized()] = $item;
            }
        }
        $this->update();
    }

    /**
     * Updates the items & callbacks of the item manager.
     */
    private function update(): void
    {
        // This edits the callbacks of the settings item.
        if (isset($this->practiceItems[self::ITEM_PLAYER_SETTINGS])) {
            $this->practiceItems[self::ITEM_PLAYER_SETTINGS]->setOnUseCallback(function (Player $player) {

                $theForm = PracticeCore::getBaseFormDisplayManager()->getForm(PracticeFormManager::FORM_SETTINGS_MENU);
                if ($theForm !== null) {
                    $theForm->display($player);
                } else {
                    $player->sendMessage(TextFormat::RED . "[ERROR-PRACTICE] Internal plugin error, unable to show the setting menu form.");
                }
                return true;
            });
        }

        if(isset($this->practiceItems[self::ITEM_PLAY_GAMES]))
        {
            $this->practiceItems[self::ITEM_PLAY_GAMES]->setOnUseCallback(function(Player $player) {

                $theForm = PracticeCore::getBaseFormDisplayManager()->getForm(PracticeFormManager::FORM_PLAY_GAMES);
                if($theForm !== null)
                {
                    $theForm->display($player);
                }
                else
                {
                    $player->sendMessage(TextFormat::RED . "[ERROR-PRACTICE] Internal plugin error, unable to show the play games form.");
                }

                return true;
            });
        }
    }

    /**
     * @param Player $player - The player we are sending the item to.
     * @param PracticeItem|null $item
     *
     * Sends the item to the player.
     */
    public function sendItem(Player $player, ?PracticeItem $item): void
    {
        if($item === null)
        {
            return;
        }

        if(!$item->doSend($player))
        {
            return;
        }

        $player->getInventory()->setItem($item->getSlot(), $item->getItem());
    }

    /**
     * @param string $type - The type of items we want to send (more or less a group of items).
     * @param Player $player - The player we are sending the item to.
     * @param bool $clearInventory - Determines whether we should clear inventory
     *                              or not before sending the items.
     *
     * Sends the items based on the type.
     */
    public function sendItemsFromType(string $type, Player $player, bool $clearInventory = true): void
    {
        if($clearInventory)
        {
            $player->getInventory()->clearAll();
            $player->getArmorInventory()->clearAll();
        }

        switch($type)
        {
            case self::TYPE_LOBBY:

                $this->sendItem($player, $this->practiceItems[self::ITEM_PLAY_GAMES]);
                $this->sendItem($player, $this->practiceItems[self::ITEM_PLAYER_SETTINGS]);
                break;
        }
    }

    /**
     * @param Item $item - The item we are searching for.
     * @return PracticeItem|null
     *
     * Overrides the PracticeItemManager as instead of searching through
     * just this current item manager, we are also searching through
     * all of the other game item managers.
     */
    public function getPracticeItem(Item $item): ?PracticeItem
    {
        $gameManagers = PracticeCore::getBaseGameManager()->getGameManagers();
        foreach($gameManagers as $gameManager) {
            $itemManager = $gameManager->getItemManager();
            if
            (
                $itemManager !== null
                && ($practiceItem = $itemManager->getPracticeItem($item)) !== null
                && $practiceItem instanceof PracticeItem
            ) {
                return $practiceItem;
            }
        }

        return parent::getPracticeItem($item);
    }
}