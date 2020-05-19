<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-19
 * Time: 13:08
 */

declare(strict_types=1);

namespace old\practice\game\inventory\menus;

use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\Player;
use old\practice\game\inventory\menus\inventories\SingleChestInv;
use old\practice\game\items\PracticeItem;
use old\practice\player\PracticePlayer;
use old\practice\PracticeCore;
use old\practice\PracticeUtil;

class LeaderboardMenu extends BaseMenu
{

    public function __construct() {

        parent::__construct(new SingleChestInv($this));

        $this->setName(PracticeUtil::getName('title-leaderboard-inv'));

        $this->setEdit(false);

        $items = PracticeCore::getItemHandler()->getLeaderboardItems();

        foreach($items as $item) {
            if($item instanceof PracticeItem) {
                $slot = $item->getSlot();
                $i = $item->getItem();
                $this->getInventory()->setItem($slot, $i);
            }
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