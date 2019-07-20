<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-03
 * Time: 18:37
 */

declare(strict_types=1);

namespace practice\game\inventory\menus\inventories;

use pocketmine\inventory\ContainerInventory;
use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\Player;
use practice\game\inventory\InventoryTask;
use practice\game\inventory\menus\BaseMenu;
use practice\game\inventory\menus\data\PracHolderData;
use practice\PracticeCore;

abstract class PracBaseInv extends ContainerInventory {

    private $size;

    const HEIGHT_ABOVE = 3;

    protected $sendDelay = 0;

    private $holders = [];

    private $menu;

    public function __construct(BaseMenu $menu, array $items = [], int $size = 0, string $title = null) {
        parent::__construct(new Vector3(), $items, $size, $title);
        $this->size = $size;
        $this->menu = $menu;
    }

    public function getMenu() : BaseMenu {
        return $this->menu;
    }

    abstract public function getTEId() : string;

    public function send(Player $player, ?string $customName) : bool {

        $pos = $player->getPosition()->floor()->add(0, self::HEIGHT_ABOVE, 0);

        $result = false;

        if($player->getLevel()->isInWorld($pos->x, $pos->y, $pos->z)) {

            $this->holders[$player->getId()] = new PracHolderData($pos, $customName);

            $this->sendPrivateInv($player, $this->holders[$player->getId()]);

            $result = true;
        }

        return $result;
    }

    public function onOpen(Player $who): void {

        $data = $this->holders[$who->getId()];

        if($data instanceof PracHolderData) {
            $this->holder = $data->getPos();
        }

        parent::onOpen($who);
        $this->holder = null;
    }

    public function onClose(Player $who): void {

        $holder = $this->holders[$who->getId()];

        if(isset($holder) and $holder instanceof PracHolderData){

            $pos = $holder->getPos();

            if($who->getLevel()->isChunkLoaded($pos->x >> 4, $pos->z >> 4)){

                $this->sendPublicInv($who, $holder);
            }

            unset($holder);

            parent::onClose($who);

        }
    }

    public function open(Player $player) : bool{

        if(!isset($this->holders[$player->getId()])){
            return false;
        }

        return parent::open($player);
    }

    public function setSendDelay(int $delay) : void {
        $this->sendDelay = $delay;
    }

    public function getSendDelay($p) : int {
        return $this->sendDelay;
    }

    abstract function sendPrivateInv(Player $player, PracHolderData $data) : void;

    abstract function sendPublicInv(Player $player, PracHolderData $data) : void;

    public function onInventorySend(Player $player) : void {

        $d = $this->getSendDelay($player);

        if($d > 0)
            PracticeCore::getInstance()->getScheduler()->scheduleDelayedTask(new InventoryTask($player, $this), $d);
        else
            $this->onSendInvSuccess($player);
    }

    public function onSendInvSuccess(Player $player) : void {
        //TODO TEST
        $id = PracticeCore::getPlayerHandler()->getOpenChestID($player);

        if(PracticeCore::getPlayerHandler()->setClosedInventoryID($id, $player))
            $player->addWindow($this, $id);
        else $this->onSendInvFail($player);
    }

    public function onSendInvFail(Player $player) : void {
        unset($this->holders[$player->getId()]);
    }

    public function getDefaultSize(): int {
        return $this->size;
    }

    protected function sendTileEntity(Player $player, Vector3 $pos, CompoundTag $tag) : void {
        $writer = new NetworkLittleEndianNBTStream();
        $tag->setString('id', $this->getTEId());
        $pkt = new BlockActorDataPacket();
        $pkt->x = $pos->x;
        $pkt->y = $pos->y;
        $pkt->z = $pos->z;
        $pkt->namedtag = $writer->write($tag);
        $player->sendDataPacket($pkt);
    }
}