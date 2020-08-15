<?php

declare(strict_types=1);

namespace jkorn\practice\items;


use jkorn\practice\player\PracticePlayer;
use jkorn\practice\PracticeUtil;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Armor;
use pocketmine\item\EnderPearl;
use pocketmine\item\Item;
use pocketmine\item\Potion;
use pocketmine\item\Snowball;
use pocketmine\item\SplashPotion;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use jkorn\practice\forms\display\manager\PracticeFormManager;
use jkorn\practice\misc\AbstractManager;
use jkorn\practice\PracticeCore;

class ItemManager extends AbstractManager
{
    const ITEM_PLAY_GAMES = "item.play.games";
    const ITEM_PLAYER_SETTINGS = "item.player.settings";

    const TYPE_LOBBY = "type.lobby";

    /** @var array|PracticeItem[] */
    private $items;

    /** @var array|TapItem[] */
    private $tapItems;

    /** @var string */
    private $resourcesFolder, $itemFolder;

    public function __construct(PracticeCore $core)
    {
        $this->resourcesFolder = $core->getResourcesFolder() . "items";
        $this->itemFolder = $core->getDataFolder() . "items";

        $this->items = [];
        $this->tapItems = [];

        parent::__construct(false);
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

        $this->initDefaultTapItems();

        $this->loadItems($inputFile);
    }

    /**
     * @param Item $item
     * @param callable $callback
     *
     * Registers the tap item to the item manager.
     */
    public function registerTapItem(Item $item, callable $callback): void
    {
        $tapItem = new TapItem($item, $callback);
        $this->tapItems[$tapItem->getLocalizedName()] = $tapItem;
    }

    /**
     * Initializes the default tap items.
     */
    private function initDefaultTapItems(): void
    {
        // Called when the player taps the enderpearl.
        $this->registerTapItem(Item::get(Item::ENDER_PEARL), function(Player $player, Item $item, int $action): bool
        {
            // TODO: Enderpearl cooldown.
            if(!$player instanceof PracticePlayer || !$item instanceof EnderPearl)
            {
                return true;
            }

            $animate = $action === PlayerInteractEvent::LEFT_CLICK_AIR && $player->getClientInfo()->isPE();
            $player->throwProjectile($item, $animate);
            return true;
        });


        // Called when the player taps the item with a potion.
        $this->registerTapItem(Item::get(Item::SPLASH_POTION), function(Player $player, Item $item, int $action): bool
        {
            if(!$player instanceof PracticePlayer || !$item instanceof SplashPotion)
            {
                return true;
            }

            $animate = $action === PlayerInteractEvent::LEFT_CLICK_AIR && $player->getClientInfo()->isPE();
            $player->throwProjectile($item, $animate);
            return true;
        });

        // Called when the player taps the item with a snowball.
        $this->registerTapItem(Item::get(Item::SNOWBALL), function(Player $player, Item $item, int $action): bool
        {
            if(!$player instanceof PracticePlayer || !$item instanceof Snowball)
            {
                return true;
            }

            $animate = $action === PlayerInteractEvent::LEFT_CLICK_AIR && $player->getClientInfo()->isPE();
            $player->throwProjectile($item, $animate);
            return true;
        });

        // Called when the player taps on a fishing rod.
        $this->registerTapItem(Item::get(Item::FISHING_ROD), function(Player $player, Item $item, int $action): bool {

            if(!$player instanceof PracticePlayer)
            {
                return true;
            }

            $animate = $action = PlayerInteractEvent::LEFT_CLICK_AIR && $player->getClientInfo()->isPE();
            $player->useRod($item, $animate);
            return true;
        });


        // Callable function for all armor.
        $armorCallable = function(Player $player, Item $item, int $action): bool
        {
            if(!$player instanceof PracticePlayer)
            {
                return true;
            }

            if(!$item instanceof Armor)
            {
                return true;
            }

            $animate = $action === PlayerInteractEvent::LEFT_CLICK_AIR && $player->getClientInfo()->isPE();
            $slot = PracticeUtil::getArmorSlotFromItem($item);
            if($slot !== -1 && ($armorInventory = $player->getArmorInventory())->getItem($slot)->isNull()) {
                $armorInventory->setItem($slot, $item);
                if(!$player->isCreative()) {
                    $player->getInventory()->setItemInHand(Item::get(Item::AIR));
                }

                if($animate) {
                    $player->sendAnimation(AnimatePacket::ACTION_SWING_ARM);
                }
            }

            return true;
        };

        // Registers the diamond armor tap items.
        $this->registerTapItem(Item::get(Item::DIAMOND_HELMET), $armorCallable);
        $this->registerTapItem(Item::get(Item::DIAMOND_CHESTPLATE), $armorCallable);
        $this->registerTapItem(Item::get(Item::DIAMOND_LEGGINGS), $armorCallable);
        $this->registerTapItem(Item::get(Item::DIAMOND_BOOTS), $armorCallable);

        // Registers the golden armor tap items.
        $this->registerTapItem(Item::get(Item::GOLD_HELMET), $armorCallable);
        $this->registerTapItem(Item::get(Item::GOLD_CHESTPLATE), $armorCallable);
        $this->registerTapItem(Item::get(Item::GOLD_LEGGINGS), $armorCallable);
        $this->registerTapItem(Item::get(Item::GOLD_BOOTS), $armorCallable);

        // Registers the iron armor tap items.
        $this->registerTapItem(Item::get(Item::IRON_HELMET), $armorCallable);
        $this->registerTapItem(Item::get(Item::IRON_CHESTPLATE), $armorCallable);
        $this->registerTapItem(Item::get(Item::IRON_LEGGINGS), $armorCallable);
        $this->registerTapItem(Item::get(Item::IRON_BOOTS), $armorCallable);

        // Registers the chain armor tap items.
        $this->registerTapItem(Item::get(Item::CHAIN_HELMET), $armorCallable);
        $this->registerTapItem(Item::get(Item::CHAIN_CHESTPLATE), $armorCallable);
        $this->registerTapItem(Item::get(Item::CHAIN_LEGGINGS), $armorCallable);
        $this->registerTapItem(Item::get(Item::CHAIN_BOOTS), $armorCallable);

        // Registers the leather armor tap items.
        $this->registerTapItem(Item::get(Item::LEATHER_HELMET), $armorCallable);
        $this->registerTapItem(Item::get(Item::LEATHER_CHESTPLATE), $armorCallable);
        $this->registerTapItem(Item::get(Item::LEATHER_LEGGINGS), $armorCallable);
        $this->registerTapItem(Item::get(Item::LEATHER_BOOTS), $armorCallable);
    }

    /**
     * @param Item $item
     * @return TapItem|null
     *
     * Gets the tap item from the
     */
    public function getTapItem(Item $item): ?TapItem
    {
        $localized = "{$item->getId()}:{$item->getDamage()}";
        if(isset($this->tapItems[$localized]))
        {
            return $this->tapItems[$localized];
        }

        return null;
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
        // This edits the callback of the settings item.
        if (isset($this->items[self::ITEM_PLAYER_SETTINGS])) {
            $this->items[self::ITEM_PLAYER_SETTINGS]->setOnUseCallback(function (Player $player) {

                $theForm = PracticeCore::getBaseFormDisplayManager()->getForm(PracticeFormManager::FORM_SETTINGS_MENU);
                if ($theForm !== null) {
                    $theForm->display($player);
                } else {
                    $player->sendMessage(TextFormat::RED . "[ERROR-PRACTICE] Internal plugin error, unable to show the setting menu form.");
                }
                return true;
            });
        }

        if(isset($this->items[self::ITEM_PLAY_GAMES]))
        {
            $this->items[self::ITEM_PLAY_GAMES]->setOnUseCallback(function(Player $player) {

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

        if(!$item->doSend($player))
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

                $this->sendItem($player, $this->items[self::ITEM_PLAY_GAMES]);
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