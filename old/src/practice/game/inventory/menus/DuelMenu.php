<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-01
 * Time: 10:59
 */

declare(strict_types=1);

namespace old\practice\game\inventory\menus;

use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\Player;
use old\practice\duels\groups\Request;
use old\practice\game\inventory\InventoryUtil;
use old\practice\game\inventory\menus\inventories\PracBaseInv;
use old\practice\game\inventory\menus\inventories\SingleChestInv;
use old\practice\game\items\PracticeItem;
use old\practice\player\PracticePlayer;
use old\practice\PracticeCore;
use old\practice\PracticeUtil;

class DuelMenu extends BaseMenu
{

    public function __construct()
    {

        parent::__construct(new SingleChestInv($this));

        $this->setName(PracticeUtil::getName('title-duel-inventory'));

        $this->setEdit(false);

        $items = PracticeCore::getItemHandler()->getDuelItems();

        foreach ($items as $item) {
            if ($item instanceof PracticeItem) {
                $slot = $item->getSlot();
                $i = $item->getItem();
                $this->getInventory()->setItem($slot, $i);
            }
        }
    }

    public function onItemMoved(PracticePlayer $p, SlotChangeAction $action): void
    {

        $origItem = $action->getSourceItem();

        $player = $p->getPlayer();

        $itemHandler = PracticeCore::getItemHandler();

        if (PracticeUtil::canUseItems($player, true) and $itemHandler->isPracticeItem($origItem)) {

            $practiceItem = $itemHandler->getPracticeItem($origItem);

            $queue = PracticeUtil::getUncoloredString($practiceItem->getName());

            $inventory = $action->getInventory();

            $player->removeWindow($inventory);

            if (PracticeCore::get1vs1Handler()->isLoadingRequest($player)) {

                $request = PracticeCore::get1vs1Handler()->getLoadedRequest($player);
                $requested = $request->getRequested();

                if (PracticeCore::getKitHandler()->isDuelKit($queue)) $request->setQueue($queue);

                if (Request::canSend($p, $requested)) PracticeCore::get1vs1Handler()->sendRequest($player, $requested);
                else PracticeCore::get1vs1Handler()->cancelRequest($request);
            }
        }
    }

    public function onInventoryClosed(Player $player): void
    {

        if (PracticeCore::get1vs1Handler()->isLoadingRequest($player)) {

            $request = PracticeCore::get1vs1Handler()->getLoadedRequest($player);
            if (!$request->hasQueue()) PracticeCore::get1vs1Handler()->cancelRequest($request);

            PracticeCore::getPlayerHandler()->setOpenInventoryID($player);
        }
    }

    public function sendTo($player): void
    {
        if (PracticeCore::getPlayerHandler()->isPlayerOnline($player)) {
            $p = PracticeCore::getPlayerHandler()->getPlayer($player);
            $pl = $p->getPlayer();
            $this->send($pl);
        }
    }
}