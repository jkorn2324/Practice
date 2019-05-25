<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-01
 * Time: 09:01
 */

declare(strict_types=1);

namespace practice\game\inventory\menus;

use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use practice\arenas\TeleportArenaTask;
use practice\game\inventory\menus\inventories\SingleChestInv;
use practice\game\items\PracticeItem;
use practice\player\PracticePlayer;
use practice\PracticeCore;
use practice\PracticeUtil;

class FFAMenu extends BaseMenu
{

    public function __construct()
    {

        parent::__construct(new SingleChestInv($this));

        $this->setName(PracticeUtil::getName('inventory.select-ffa'));

        $this->setEdit(false);

        $items = PracticeCore::getItemHandler()->getFFAItems();

        foreach ($items as $item) {
            if ($item instanceof PracticeItem) {
                $i = clone $item->getItem();
                $name = PracticeUtil::getUncoloredString($item->getName());
                $numPlayers = PracticeCore::getArenaHandler()->getNumPlayersInArena($name);
                $lore = ["\n" . TextFormat::GREEN . 'Players: ' . $numPlayers];

                $properCount = PracticeUtil::getProperCount($numPlayers);

                $i = $i->setLore($lore)->setCount($properCount);
                $slot = $item->getSlot();
                $this->getInventory()->setItem($slot, $i);
            }
        }
    }

    public function onItemMoved(PracticePlayer $p, SlotChangeAction $action): void
    {

        $origItem = $action->getSourceItem();

        $player = $p->getPlayer();

        if (PracticeUtil::canUseItems($player, true) and PracticeCore::getItemHandler()->isPracticeItem($origItem)) {

            $practiceItem = PracticeCore::getItemHandler()->getPracticeItem($origItem);
            $arenaName = $practiceItem->getName();

            if (PracticeCore::getArenaHandler()->isFFAArena($arenaName)) {

                $arena = PracticeCore::getArenaHandler()->getFFAArena($arenaName);
                $inv = $action->getInventory();
                $player->removeWindow($inv);

                PracticeCore::getInstance()->getScheduler()->scheduleDelayedTask(new TeleportArenaTask($p, $arena), 5);
            }
        }
    }

    public function onInventoryClosed(Player $player): void
    {
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