<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-19
 * Time: 11:16
 */

declare(strict_types=1);

namespace practice\kits;

use pocketmine\entity\Effect;
use pocketmine\item\Item;
use pocketmine\Player;
use practice\game\effects\PracticeEffect;
use practice\player\PracticePlayer;
use practice\PracticeCore;
use practice\PracticeUtil;

class Kit
{

    public const NO_KIT = 'none';

    private $items;

    private $name;

    private $armor;

    private $effects;

    private $repItem;

    public function __construct(string $name, array $items = [], array $armor = [], array $effects = [], $repItem = null)
    {
        $this->name = $name;
        $this->items = $items;
        $this->armor = $armor;
        $this->effects = $effects;
        $this->repItem = (is_null($repItem) ? Item::get(0) : $repItem);
    }

    public function setArmor(array $armor = []) : Kit {
        $this->armor = $armor;
        return $this;
    }

    public function setItems(array $items = []) : Kit{
        $this->items = $items;
        return $this;
    }

    public function setEffects(array $effects = []) : Kit {
        $this->effects = $effects;
        return $this;
    }

    public function setRepItem(Item $item) : Kit {
        $this->repItem = $item;
        return $this;
    }

    public function giveTo($player, bool $msg = false) : void {

        $p = null;

        if($player instanceof PracticePlayer){
            if($player->isOnline()) $p = $player;
        } else if ($player instanceof Player){
            if(PracticeCore::getPlayerHandler()->isPlayer($player)){
                $p = PracticeCore::getPlayerHandler()->getPlayer($player);
            }
        }

        if(!is_null($p)){

            if($msg === true){
                $str = PracticeUtil::getMessage('general.kits.receive');
                $str = strval(str_replace('%kit%', $this->name, $str));
                $p->sendMessage($str);
            }

            $p->setHasKit(true);
            $pl = $p->getPlayer();
            $itemInv = $pl->getInventory();
            $armorInv = $pl->getArmorInventory();

            $itemInv->clearAll();
            $armorInv->clearAll();

            $count = 0;

            foreach($this->items as $item){
                if($item instanceof Item){
                    $itemInv->setItem($count, $item);
                    $count++;
                }
            }

            $keys = array_keys($this->armor);

            foreach($keys as $key){
                $armor = $this->armor[$key];
                if($armor instanceof Item){
                    $slot = PracticeUtil::getArmorFromKey($key);
                    if($slot !== -1) $armorInv->setItem($slot, $armor);
                }
            }

            foreach($this->effects as $effect){
                if($effect instanceof PracticeEffect){
                    $effect->applyTo($pl);
                }
            }
        }
    }

    public function toMap() : array {

        $items = [];
        $armor = [];
        $effects = [];

        $repID = $this->getRepItem()->getId();
        $repDmg = $this->getRepItem()->getDamage();

        $repItem = ($this->hasRepItem() ? $repID . ':' . $repDmg : '0:0');

        for($i = 0; $i < count($this->items); $i++){

            $item = $this->items[$i];

            if($item instanceof Item){

                $id = $item->getId();
                $damage = $item->getDamage();
                $count = $item->getCount();

                $itemName = $item->getName();

                if($id === Item::GOLDEN_APPLE and $itemName === PracticeUtil::getName('golden-head'))
                    $damage = 1;


                $str = $id . ':' . $damage . ':' . $count;

                if($item->hasEnchantments()){
                    $str .= '-';
                    $enchantCount = 0;
                    $len = count($item->getEnchantments()) - 1;
                    foreach($item->getEnchantments() as $enchant){
                        $comma = ($enchantCount === $len) ? '' : ',';
                        $str .= $enchant->getId() . ':' . $enchant->getLevel() . $comma;
                        $enchantCount++;
                    }
                }
                $items[] = $str;
            }
        }

        foreach(array_keys($this->armor) as $key){

            $item = $this->armor[$key];

            if($item instanceof Item){
                $str = $item->getId() . ':' . $item->getDamage() . ':' . $item->getCount();

                if($item->hasEnchantments()){

                    $str .= '-';
                    $count = 0;
                    $len = count($item->getEnchantments()) - 1;
                    foreach($item->getEnchantments() as $enchant){
                        $comma = ($count === $len ? '' : ',');
                        $str .= $enchant->getId() . ':' . $enchant->getLevel() . $comma;
                        $count++;
                    }
                }
                $armor[$key] = $str;
            }
        }

        for($i = 0; $i < count($this->effects); $i++){
            $effect = $this->effects[$i];
            if($effect instanceof PracticeEffect){
                $str = $effect->toString();
                $effects[] = $str;
            }
        }

        $result = [
            'effects' => $effects,
            'items' => $items,
            'armor' => $armor,
            'rep-item' => $repItem
        ];

        return $result;
    }

    public function hasRepItem() : bool {
        return !is_null($this->repItem) and $this->repItem->getId() !== 0;
    }

    public function getRepItem() : Item {
        return $this->repItem->setCustomName(PracticeUtil::str_replace(PracticeUtil::getName("kit-name"), ["%kit-name%" => $this->name]));
    }

    public function getName() : string {
        return $this->name;
    }

    public function removeEffect(int $id) : void {
        if($this->hasEffect($id)) {
            $effect = $this->getEffect($id);
            unset($effect);
            $this->effects = array_values($this->effects);
        }
    }

    public function addEffect(PracticeEffect $effect) : void {
        array_push($this->effects, $effect);
    }

    private function indexOf(int $effectId) : int {
        $result = -1;
        for($i = 0; $i < count($this->effects); $i++) {
            $effect = $this->effects[$i];
            if($effect instanceof PracticeEffect) {
                if($effect->getEffect()->getId() === $effectId) {
                    $result = $i;
                    break;
                }
            }
        }
        return $result;
    }

    public function hasEffect(int $id) : bool {
        return $this->indexOf($id) !== -1;
    }

    private function getEffect(int $id) {
        return $this->effects[$this->indexOf($id)];
    }

    public function getLocalizedName() : string {
        $local = PracticeUtil::str_replace($this->name, [' ' => '']);
        return strtolower($local);
    }
}