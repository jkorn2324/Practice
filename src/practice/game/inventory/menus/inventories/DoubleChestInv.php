<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-03
 * Time: 19:40
 */

namespace practice\game\inventory\menus\inventories;


use pocketmine\block\Block;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\Player;
use pocketmine\tile\Tile;
use practice\game\inventory\menus\BaseMenu;
use practice\game\inventory\menus\data\PracHolderData;
use practice\PracticeCore;

class DoubleChestInv extends PracBaseInv
{

    const CHEST_SIZE = 54;

    public function __construct(BaseMenu $menu, array $items = []) {
        parent::__construct($menu, $items, self::CHEST_SIZE, null);
    }

    public function getName(): string {
        return 'Practice Chest';
    }

    /**
     * Returns the Minecraft PE inventory type used to show the inventory window to clients.
     * @return int
     */
    public function getNetworkType(): int {
        return WindowTypes::CONTAINER;
    }

    public function getTEId(): string {
        return Tile::CHEST;
    }

    function sendPrivateInv(Player $player, PracHolderData $data): void {

        $block = Block::get(Block::CHEST)->setComponents($data->getPos()->x, $data->getPos()->y, $data->getPos()->z);
        $block2 = Block::get(Block::CHEST)->setComponents($data->getPos()->x + 1, $data->getPos()->y, $data->getPos()->z);

        $player->getLevel()->sendBlocks([$player], [$block, $block2]);

        $tag = new CompoundTag();
        if(!is_null($data->getCustomName())){
            $tag->setString('CustomName', $data->getCustomName());
        }

        $tag->setInt('pairz', $block->z);

        $tag->setInt('pairx', $block->x + 1);
        $this->sendTileEntity($player, $block, $tag);

        $tag->setInt('pairx', $block->x);
        $this->sendTileEntity($player, $block2, $tag);

        $this->onInventorySend($player);
    }

    function sendPublicInv(Player $player, PracHolderData $data): void {
        $player->getLevel()->sendBlocks([$player], [$data->getPos(), $data->getPos()->add(1, 0, 0)]);
    }

    public function getSendDelay($pl): int {

        $ping = 300;

        if(PracticeCore::getPlayerHandler()->isPlayerOnline($pl)) {
            $p = PracticeCore::getPlayerHandler()->getPlayer($pl);
            $ping = $p->getPing();
        }

        return $ping < 280 ? 5 : 2;
    }
}