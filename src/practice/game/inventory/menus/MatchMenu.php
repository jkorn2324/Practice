<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-01
 * Time: 10:28
 */

declare(strict_types=1);

namespace practice\game\inventory\menus;

use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use practice\game\inventory\InventoryUtil;
use practice\game\inventory\menus\inventories\SingleChestInv;
use practice\game\items\PracticeItem;
use practice\player\PracticePlayer;
use practice\PracticeCore;
use practice\PracticeUtil;

class MatchMenu extends BaseMenu
{

    private $ranked;

    public function __construct(bool $ranked)
    {

        parent::__construct(new SingleChestInv($this));

        $this->ranked = $ranked;

        $name = PracticeUtil::getName('inventory.select-duel');
        $name = PracticeUtil::str_replace($name, ['%ranked%' => ($ranked ? 'Ranked' : 'Unranked')]);

        $this->setName($name);

        $this->setEdit(false);

        $items = PracticeCore::getItemHandler()->getDuelItems();

        foreach ($items as $item) {

            if ($item instanceof PracticeItem) {

                $name = $item->getName();

                $uncolored = PracticeUtil::getUncoloredString($name);

                $numInQueue = PracticeCore::getDuelHandler()->getNumQueuedFor($uncolored, $ranked);

                $numInFights = PracticeCore::getDuelHandler()->getNumFightsFor($uncolored, $ranked);

                $inQueues = "\n" . TextFormat::GREEN . 'In-Queues: ' . TextFormat::WHITE . $numInQueue;

                $inFights = "\n" . TextFormat::GREEN . 'In-Fights: ' . TextFormat::WHITE . $numInFights;

                $lore = [$inQueues, $inFights];

                $properCount = PracticeUtil::getProperCount($numInQueue);

                $slot = $item->getSlot();
                $i = clone $item->getItem();
                $i = $i->setLore($lore)->setCount($properCount);
                $this->getInventory()->setItem($slot, $i);
            }
        }
    }

    public function onItemMoved(PracticePlayer $p, SlotChangeAction $action): void {

        $origItem = $action->getSourceItem();

        $newItem = $action->getTargetItem();

        $player = $p->getPlayer();

        $itemHandler = PracticeCore::getItemHandler();

        if(PracticeUtil::isPotion($origItem, true))
            $origItem = $origItem->setCount(1);

        if(PracticeUtil::isPotion($newItem, true))
            $newItem = $newItem->setCount(1);

        $isPracItem = ($itemHandler->isPracticeItem($origItem)) or ($itemHandler->isPracticeItem($newItem));

        if (PracticeUtil::canUseItems($player, true) and $isPracItem === true) {

            $practiceItem = ($itemHandler->isPracticeItem($newItem)) ? $itemHandler->getPracticeItem($newItem) : $itemHandler->getPracticeItem($origItem);

            $queue = $practiceItem->getName();

            $q = PracticeUtil::getUncoloredString($queue);

            $player->removeWindow($action->getInventory());

            $duelHandler = PracticeCore::getDuelHandler();

            if (PracticeCore::getKitHandler()->isDuelKit($q) and !$duelHandler->isPlayerInQueue($player)) $duelHandler->addPlayerToQueue($p->getPlayerName(), $q, $this->ranked);
        }
    }

    public function onInventoryClosed(Player $player): void {
        PracticeCore::getPlayerHandler()->setOpenInventoryID($player);
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