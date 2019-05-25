<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-01
 * Time: 13:38
 */

declare(strict_types=1);

namespace practice\duels\misc;


use pocketmine\block\Skull;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use practice\PracticeUtil;

class DuelInvInfo
{

    private $items;

    private $armor;

    private $health;

    private $hunger;

    private $numHits;

    private $playerName;

    private $queue;

    private $potionCount;

    private $soupCount;

    public function __construct(Player $player, string $queue, int $numHits) {

        $this->queue = $queue;
        $this->items = [];
        $this->armor = [];
        $this->health = intval(round($player->getHealth()));
        $this->hunger = intval(round($player->getFood()));
        $this->playerName = $player->getName();

        $this->potionCount = 0;
        $this->soupCount = 0;

        $this->numHits = $numHits;

        $arr = PracticeUtil::inventoryToArray($player->getPlayer(), true);

        $itemArr = $arr["items"];
        $armorArr = $arr["armor"];

        $armorKeys = ["helmet" => 0, "chestplate" => 1, "leggings" => 2, "boots" => 3];

        $keys = array_keys($armorArr);

        foreach($keys as $key) {

            $index = $armorKeys[$key];
            $val = $armorArr[$key];

            if($val instanceof Item) $this->armor[$index] = $val;
        }

        foreach($itemArr as $item) {
            if($item instanceof Item) {
                $this->items[] = $item;
                if($this->displayPots() === true and $item->getId() === Item::SPLASH_POTION) $this->potionCount++;
                elseif ($this->displaySoup() === true and $item->getId() === Item::MUSHROOM_STEW) $this->soupCount++;
            }
        }
    }

    private function displayPots() : bool {
        return PracticeUtil::equals_string($this->queue, "NoDebuff", "nodebuff", "NODEBUFF", "PotPvP", "PotionPvP", "No Debuff", "No-Debuff");
    }

    private function displaySoup() : bool {
        return PracticeUtil::equals_string($this->queue, "SoupPvP", "Soup", "soup", "SOUP", "Soup-PvP", "Soup PvP");
    }

    public function getPlayerName() : string {
        return $this->playerName;
    }

    /**
     * @return array|Item[]
     */
    public function getArmor() : array {
        return $this->armor;
    }

    /**
     * @return array|Item[]
     */
    public function getItems() : array {
        return $this->items;
    }

    public function getHealth() : int {
        return $this->health;
    }

    public function getHunger() : int {
        return $this->hunger;
    }

    public function getNumHits() : int {
        return $this->numHits;
    }

    public function getItem() : Item {
        return Item::get(Item::NETHER_STAR, 0, 1)->setCustomName(TextFormat::RED . $this->playerName);
    }

    /**
     * @return array|Item[]
     */
    public function getStatsItems() : array {

        $head = Item::get(Item::MOB_HEAD, 3, 1)->setCustomName(TextFormat::YELLOW . $this->playerName . TextFormat::RESET);

        $healthItem = Item::get(Item::GLISTERING_MELON, 1, PracticeUtil::getProperCount($this->getHealth()))->setCustomName(TextFormat::RED . "$this->health HP");

        $numHitsItem = Item::get(Item::PAPER, 0, PracticeUtil::getProperCount($this->getNumHits()))->setCustomName(TextFormat::GOLD . "$this->numHits Hits");

        $hungerItem = Item::get(Item::STEAK, 0, PracticeUtil::getProperCount($this->getHunger()))->setCustomName(TextFormat::GREEN . "$this->hunger Hunger-Points");

        $numPots = Item::get(Item::SPLASH_POTION, 21, PracticeUtil::getProperCount($this->potionCount))->setCustomName(TextFormat::AQUA . "$this->potionCount Pots");

        $numSoup = Item::get(Item::MUSHROOM_STEW, 0, PracticeUtil::getProperCount($this->soupCount))->setCustomName(TextFormat::BLUE . "$this->soupCount Soup");

        $arr = [$head, $healthItem, $hungerItem, $numHitsItem];

        if($this->displayPots()) $arr[] = $numPots;
        elseif ($this->displaySoup()) $arr[] = $numSoup;

        return $arr;
    }
}