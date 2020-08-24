<?php

declare(strict_types=1);

namespace jkorn\practice\items;


use jkorn\practice\player\PracticePlayer;
use jkorn\practice\PracticeUtil;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Armor;
use pocketmine\item\EnderPearl;
use pocketmine\item\Item;
use pocketmine\item\Snowball;
use pocketmine\item\SplashPotion;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\Player;

class TapItemManager
{

    /** @var TapItem[] */
    private static $tapItems = [];

    /**
     * @param Item $item - The tap item.
     * @param callable $callback - The callback when the tap item is used.
     * @param bool $includeMeta - Determines whether we should check for metadata or not.
     *
     * Registers the tap item to the items list.
     */
    public static function registerTapItem(Item $item, callable $callback, bool $includeMeta = true): void
    {
        $tapItem = new TapItem($item, $callback, $includeMeta);
        self::$tapItems[$item->getId()] = $tapItem;
    }

    /**
     * Initializes the default tap items.
     */
    public static function initializeDefaults(): void
    {
        // Called when the player taps the enderpearl.
        self::registerTapItem(Item::get(Item::ENDER_PEARL), function(Player $player, Item $item, int $action): bool
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
        self::registerTapItem(Item::get(Item::SPLASH_POTION), function(Player $player, Item $item, int $action): bool
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
        self::registerTapItem(Item::get(Item::SNOWBALL), function(Player $player, Item $item, int $action): bool
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
        self::registerTapItem(Item::get(Item::FISHING_ROD), function(Player $player, Item $item, int $action): bool {

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
        self::registerTapItem(Item::get(Item::DIAMOND_HELMET), $armorCallable);
        self::registerTapItem(Item::get(Item::DIAMOND_CHESTPLATE), $armorCallable);
        self::registerTapItem(Item::get(Item::DIAMOND_LEGGINGS), $armorCallable);
        self::registerTapItem(Item::get(Item::DIAMOND_BOOTS), $armorCallable);

        // Registers the golden armor tap items.
        self::registerTapItem(Item::get(Item::GOLD_HELMET), $armorCallable);
        self::registerTapItem(Item::get(Item::GOLD_CHESTPLATE), $armorCallable);
        self::registerTapItem(Item::get(Item::GOLD_LEGGINGS), $armorCallable);
        self::registerTapItem(Item::get(Item::GOLD_BOOTS), $armorCallable);

        // Registers the iron armor tap items.
        self::registerTapItem(Item::get(Item::IRON_HELMET), $armorCallable);
        self::registerTapItem(Item::get(Item::IRON_CHESTPLATE), $armorCallable);
        self::registerTapItem(Item::get(Item::IRON_LEGGINGS), $armorCallable);
        self::registerTapItem(Item::get(Item::IRON_BOOTS), $armorCallable);

        // Registers the chain armor tap items.
        self::registerTapItem(Item::get(Item::CHAIN_HELMET), $armorCallable);
        self::registerTapItem(Item::get(Item::CHAIN_CHESTPLATE), $armorCallable);
        self::registerTapItem(Item::get(Item::CHAIN_LEGGINGS), $armorCallable);
        self::registerTapItem(Item::get(Item::CHAIN_BOOTS), $armorCallable);

        // Registers the leather armor tap items.
        self::registerTapItem(Item::get(Item::LEATHER_HELMET), $armorCallable);
        self::registerTapItem(Item::get(Item::LEATHER_CHESTPLATE), $armorCallable);
        self::registerTapItem(Item::get(Item::LEATHER_LEGGINGS), $armorCallable);
        self::registerTapItem(Item::get(Item::LEATHER_BOOTS), $armorCallable);
    }

    /**
     * @param Item $item
     *
     * Unregisters the tap item from the manager.
     */
    public static function unregisterTapItem(Item $item): void
    {
        if(isset(self::$tapItems[$item->getId()])) {
            unset(self::$tapItems[$item->getId()]);
        }
    }

    /**
     * @param Item $item - The item we are testing for.
     * @return TapItem|null
     *
     * Gets the tap item from the item class.
     */
    public static function getTapItem(Item $item): ?TapItem
    {
        if(isset(self::$tapItems[$item->getId()])) {
            $tapItem = self::$tapItems[$item->getId()];
            if($tapItem->equalsItem($item)) {
                return $tapItem;
            }
        }
        return null;
    }
}