<?php

declare(strict_types=1);

namespace jkorn\practice\player\misc;


use Closure;
use jkorn\practice\player\PracticePlayer;
use jkorn\practice\PracticeCore;
use pocketmine\Server;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\scheduler\ClosureTask;
use pocketmine\entity\Attribute;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;

/**
 * Class FormURLImageHandler
 * @package jkorn\practice\player\misc
 *
 * This class was taken from Muqsit's FormImagesFix plugin. Implemented
 * here so textures and icons for forms could be easily added.
 */
class FormURLImageHandler
{

    private const REQUEST_COUNT = 3;

    /** @var PracticePlayer */
    private $player;
    /** @var Server */
    private $server;

    /** @var Closure[] */
    private $callbackResponses = [];

    /** @var bool - Determines whether or not to request the update. */
    private $doRequestUpdate = false;

    /** @var int */
    private $lastUpdateTickDifference = 0, $numberUpdateTimes = self::REQUEST_COUNT - 1;

    public function __construct(PracticePlayer $player)
    {
        $this->player = $player;
        $this->server = $player->getServer();
    }

    /**
     * Called when the form is sent.
     */
    public function onSend(): void
    {
        PracticeCore::getInstance()->getScheduler()->scheduleDelayedTask(
            new ClosureTask(function(int $currentTick): void
            {
                $this->onSendDelay();
            }), 1);
    }

    /**
     * Called in onSend.
     */
    private function onSendDelay(): void
    {
        if(!$this->player->isOnline())
        {
            return;
        }

        $timeStamp = mt_rand() * 1000;
        $packet = new NetworkStackLatencyPacket();
        $packet->timestamp = $timeStamp;
        $packet->needResponse = true;
        $this->player->sendDataPacket($packet);
        $this->callbackResponses[$timeStamp] = true;
    }

    /**
     * @param $timeStamp - The timestamp received from the packet.
     * @return bool - Returns true if we requested the form update, otherwise returns false.
     *
     * Called when the packet has been received.
     */
    public function onReceive($timeStamp): bool
    {
        if
        (
            !$this->player->isOnline()
            || !isset($this->callbackResponses[$timeStamp]))
        {
            return false;
        }

        unset($this->callbackResponses[$timeStamp]);
        $this->requestFormUpdate();
        return true;
    }

    /**
     * Updates the url image manager.
     */
    public function update(): void
    {
        if($this->doRequestUpdate)
        {
            $this->lastUpdateTickDifference++;
            if($this->lastUpdateTickDifference >= 10)
            {
                $this->requestFormUpdate();
            }
        }
    }

    /**
     * Sends a request to the player to update the form's images.
     */
    private function requestFormUpdate(): void
    {
        if(self::REQUEST_COUNT > 0 && !$this->doRequestUpdate)
        {
            $this->lastUpdateTickDifference = 0;
            $this->numberUpdateTimes = self::REQUEST_COUNT - 1;
            $this->doRequestUpdate = true;
        }
        elseif ($this->doRequestUpdate)
        {
            $this->numberUpdateTimes--;

            if($this->numberUpdateTimes < 0)
            {
                $this->doRequestUpdate = false;
                $this->lastUpdateTickDifference = 0;
            }
        }

        if($this->doRequestUpdate && $this->player->isOnline())
        {
            $packet = new UpdateAttributesPacket();
            $packet->entityRuntimeId = $this->player->getId();
            $entries[] = $this->player->getAttributeMap()->getAttribute(Attribute::EXPERIENCE_LEVEL);
            $this->player->sendDataPacket($packet);
        }
    }
}