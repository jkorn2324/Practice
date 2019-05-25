<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-01
 * Time: 08:44
 */

declare(strict_types=1);

namespace practice\game\inventory\menus;


use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\Player;
use practice\duels\misc\DuelInvInfo;
use practice\game\inventory\menus\inventories\DoubleChestInv;
use practice\player\PracticePlayer;
use practice\PracticeCore;
use practice\PracticeUtil;

class ResultMenu extends BaseMenu {

    public function __construct(DuelInvInfo $info) {

        parent::__construct(new DoubleChestInv($this));

        $name = PracticeUtil::getName('duel-result-inventory');
        $name = PracticeUtil::str_replace($name, ['%player%' => $info->getPlayerName()]);

        $this->setEdit(false);

        $this->setName($name);

        $allItems = [];

        $count = 0;

        $row = 0;

        $maxRows = 3;

        $items = $info->getItems();

        foreach($items as $item) {

            $currentRow = $maxRows - $row;
            $v = ($currentRow + 1) * 9;

            $val = -1;

            if($row === 0) {
                $v = $v - 9;
                $val = intval(($count % 9) + $v);
            } else $val = $count - 9;

            if($val != -1) $allItems[$val] = $item;

            $count++;

            if($count % 9 == 0 and $count != 0) $row++;
        }

        $row = $maxRows + 1;
        $lastRowIndex = ($row + 1) * 9;
        $secLastRowIndex = $row * 9;

        $armorItems = $info->getArmor();

        foreach($armorItems as $armor) {
            $allItems[$secLastRowIndex] = $armor;
            $secLastRowIndex++;
        }

        $statsItems = $info->getStatsItems();

        foreach($statsItems as $statsItem) {
            $allItems[$lastRowIndex] = $statsItem;
            $lastRowIndex++;
        }

        $keys = array_keys($allItems);

        foreach($keys as $index) {
            $index = intval($index);
            $item = $allItems[$index];
            $this->getInventory()->setItem($index, $item);
        }
    }

    public function onItemMoved(PracticePlayer $p, SlotChangeAction $action): void {}

    public function onInventoryClosed(Player $player): void {
        PracticeCore::getPlayerHandler()->setOpenInventoryID($player);
    }

    public function sendTo($player): void {
        if(PracticeCore::getPlayerHandler()->isPlayerOnline($player)) {
            $p = PracticeCore::getPlayerHandler()->getPlayer($player);
            $pl = $p->getPlayer();
            $this->send($pl);
        }
    }
}