<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-10
 * Time: 16:37
 */

declare(strict_types=1);

namespace practice\scoreboard;

use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use practice\player\PracticePlayer;

class Scoreboard
{

    private const SORT_ASCENDING = 0;

    private const SORT_DESCENDING = 1;

    private const SLOT_SIDEBAR = "sidebar";

    /* @var ScorePacketEntry[] */
    private $lines;

    private $title;

    private $deviceOS;

    private $player;

    public function __construct(PracticePlayer $player, string $title) {
        $this->deviceOS = $player->getDevice();
        $this->player = $player->getPlayer();
        $this->title = $title;
        $this->lines = [];
        $this->initScoreboard();
    }

    private function initScoreboard() : void {

        $pkt = new SetDisplayObjectivePacket();
        $pkt->objectiveName = $this->player->getName();
        $pkt->displayName = $this->title;
        $pkt->sortOrder = self::SORT_ASCENDING;
        $pkt->displaySlot = self::SLOT_SIDEBAR;
        $pkt->criteriaName = "dummy";

        $this->player->dataPacket($pkt);
    }

    public function clearScoreboard() : void {

        $packet = new SetScorePacket();

        $packet->entries = $this->lines;

        $packet->type = SetScorePacket::TYPE_REMOVE;

        $this->player->dataPacket($packet);

        $this->lines = [];
    }

    public function addLine(int $id, string $line) : void {

        $entry = new ScorePacketEntry();

        $entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;

        if(isset($this->lines[$id])) {

            $pkt = new SetScorePacket();

            $pkt->entries[] = $this->lines[$id];

            $pkt->type = SetScorePacket::TYPE_REMOVE;

            $this->player->dataPacket($pkt);

            unset($this->lines[$id]);
        }

        $entry->score = $id;

        $entry->scoreboardId = $id;

        $entry->entityUniqueId = $this->player->getId();

        $entry->objectiveName = $this->player->getName();

        $entry->customName = $line;

        $this->lines[$id] = $entry;

        $pkt = new SetScorePacket();

        $pkt->entries[] = $entry;

        $pkt->type = SetScorePacket::TYPE_CHANGE;

        $this->player->dataPacket($pkt);
    }

    public function removeLine(int $id) : void {

        if(isset($this->lines[$id])) {

            $line = $this->lines[$id];

            $packet = new SetScorePacket();

            $packet->entries[] = $line;

            $packet->type = SetScorePacket::TYPE_REMOVE;

            $this->player->dataPacket($packet);
        }

        unset($this->lines[$id]);
    }

    public function removeScoreboard() : void {

        $pkt = new RemoveObjectivePacket();

        $pkt->objectiveName = $this->player->getName();

        $this->player->dataPacket($pkt);
    }

    public function resendScoreboard() : void {
        $this->initScoreboard();
    }
}