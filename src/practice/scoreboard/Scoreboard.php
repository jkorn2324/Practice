<?php

declare(strict_types=1);


namespace practice\scoreboard;


use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\Player;

class Scoreboard
{
    const SORT_ASCENDING = 0;
    const SORT_DESCENDING = 1;
    const SLOT_SIDEBAR = 'sidebar';

    /* @var ScorePacketEntry[] */
    private $lines;

    /* @var string */
    private $title;

    /* @var Player */
    private $player;


    public function __construct(Player $player, string $title)
    {
        $this->title = $title;
        $this->lines = [];
        $this->player = $player;
        $this->initScoreboard();
    }

    /**
     * Initializes the scoreboard to the player.
     */
    private function initScoreboard() : void {

        $pkt = new SetDisplayObjectivePacket();
        $pkt->objectiveName = $this->player->getName();
        $pkt->displayName = $this->title;
        $pkt->sortOrder = self::SORT_ASCENDING;
        $pkt->displaySlot = self::SLOT_SIDEBAR;
        $pkt->criteriaName = 'dummy';
        $this->player->sendDataPacket($pkt);
    }

    /**
     * Clears the scoreboard from the player.
     */
    public function clearScoreboard() : void {

        $pkt = new SetScorePacket();

        $pkt->entries = $this->lines;

        $pkt->type = SetScorePacket::TYPE_REMOVE;

        $this->player->sendDataPacket($pkt);

        $this->lines = [];
    }

    /**
     * @param int $id
     * @param string $line
     *
     * Adds a line to the scoreboard.
     */
    public function addLine(int $id, string $line) : void {

        $entry = new ScorePacketEntry();

        $entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;

        if(isset($this->lines[$id])) {

            $pkt = new SetScorePacket();

            $pkt->entries[] = $this->lines[$id];

            $pkt->type = SetScorePacket::TYPE_REMOVE;

            $this->player->sendDataPacket($pkt);

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

        $this->player->sendDataPacket($pkt);
    }


    /**
     * @param int $id
     *
     * Adds an empty line to the scoreboard.
     */
    public function addEmptyLine(int $id): void
    {
        $lineData = str_repeat(" ", $id);
        $this->addLine($id, $lineData);
    }

    /**
     * @param int $id
     *
     * Removes the line from the scoreboard.
     */
    public function removeLine(int $id) : void {

        if(isset($this->lines[$id])) {

            $line = $this->lines[$id];

            $packet = new SetScorePacket();

            $packet->entries[] = $line;

            $packet->type = SetScorePacket::TYPE_REMOVE;

            $this->player->sendDataPacket($packet);

            unset($this->lines[$id]);
        }
    }

    /**
     * @param int $id
     * @return string|null
     *
     * Gets the line from the scoreboard.
     */
    public function getLine(int $id): ?string
    {
        if(isset($this->lines[$id]))
        {
            return $this->lines[$id]->customName;
        }

        return null;
    }

    /**
     * Removes the scoreboard from the player.
     */
    public function removeScoreboard() : void {

        $packet = new RemoveObjectivePacket();

        $packet->objectiveName = $this->player->getName();

        $this->player->sendDataPacket($packet);
    }

    /**
     * Resends the scoreboard to the player.
     */
    public function resendScoreboard() : void
    {
        $this->initScoreboard();
    }
}