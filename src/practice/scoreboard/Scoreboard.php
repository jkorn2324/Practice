<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-10
 * Time: 16:37
 */

namespace practice\scoreboard;


use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\Player;

use pocketmine\utils\TextFormat;
use practice\PracticeUtil;
use practice\scoreboard\ScoreboardLine;

class Scoreboard
{
    private const SORT_ASCENDING = 0;

    private const SORT_DESCENDING = 1;

    private const SLOT_SIDEBAR = "sidebar";

    public const SPAWN_SCOREBOARD = "board.spawn";

    public const SPEC_SCOREBOARD = "board.spec";

    public const DUEL_SCOREBOARD = "board.duel";

    public const FFA_SCOREBOARD = "board.ffa";

    public const NO_SCOREBOARD = "board.none";

    private $title;

    private $player;

    /* @var ScoreboardLine[] */
    private $lines;

    private $separatorCount = 0;

    private $deviceOS;

    private $type;

    private $sent;

    public function __construct(Player $p, int $device, string $title, string $type) {
        $this->title = $title;
        $this->player = $p;
        $this->deviceOS = $device;
        $this->type = $type;
        $this->lines = [];
        $this->sent = false;
    }

    public static function isValidBoardType(string $type) : bool {
        return $type === self::SPEC_SCOREBOARD or $type === self::SPAWN_SCOREBOARD or $type === self::DUEL_SCOREBOARD or $type === self::FFA_SCOREBOARD or $type === self::NO_SCOREBOARD;
    }

    public function getType() : string {
        return $this->type;
    }

    public function addLine(string $key, string $text) : self {
        $id = count($this->lines);
        $line = new DataLine($id, $text);
        $this->lines[$key] = $line;
        return $this;
    }

    public function addSeparator(string $format, bool $visible = true) : self {
        $id = count($this->lines);
        $line = new SeparatorLine($id, $format, $visible);
        $this->separatorCount++;
        $key = "separator-" . $this->separatorCount;
        $this->lines[$key] = $line;
        return $this;
    }

    public function hideLine($obj, bool $by_key = true) : self {

        $keys = array_keys($this->lines);

        foreach ($keys as $key) {
            $value = $this->lines[$key];
            if($by_key === true) {
                if($obj === $key) {
                    $value = $value->setHidden(true);
                    $this->lines[$key] = $value;
                    break;
                }
            } else {
                $id = $value->getId();
                if($obj === $id) {
                    $value = $value->setHidden(true);
                    $this->lines[$key] = $value;
                    break;
                }
            }
        }
        return $this;
    }

    public function showLine($obj, bool $by_key = true) : self {

        $keys = array_keys($this->lines);

        foreach($keys as $key) {
            $value = $this->lines[$key];
            if($by_key === true) {
                if($obj === $key) {
                    $value = $value->setHidden(false);
                    $this->lines[$key] = $value;
                    break;
                }
            } else {
                $id = $value->getId();
                if($obj === $id) {
                    $value = $value->setHidden(false);
                    $this->lines[$key] = $value;
                    break;
                }
            }
        }

        return $this;
    }

    public function remove() : void {
        $pkt = new RemoveObjectivePacket();
        $pkt->objectiveName = $this->player->getName();
        $this->player->dataPacket($pkt);
    }

    public function send() : void {

        $packets = $this->build();

        foreach($packets as $packet) {
            $this->player->dataPacket($packet);
        }

        $this->sent = true;
    }

    public function resendLine(string $key, array $lines) : void {

        if($this->sent === true) {
            $value = null;

            if(array_key_exists($key, $this->lines)) {
                $line = $this->lines[$key];
                if($line instanceof DataLine) {
                    $this->lines[$key] = $line->updateText($lines);
                    $value = $this->lines[$key];
                }

                if(!is_null($value) and $value instanceof DataLine) {

                    $entry = new ScorePacketEntry();

                    $entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;

                    $entry->objectiveName = $this->player->getName();

                    $entry->entityUniqueId = $this->player->getId();

                    $id = $value->getId();

                    $text = $value->getText();

                    $entry->scoreboardId = $id;

                    $entry->customName = $text;

                    $entry->score = $id;

                    $packet = new SetScorePacket();

                    $packet->entries[] = $entry;

                    $packet->type = SetScorePacket::TYPE_REMOVE;

                    $this->player->dataPacket($packet);

                    if($value->isHidden() === false) {

                        $packet = new SetScorePacket();

                        $packet->entries[] = $entry;

                        $packet->type = SetScorePacket::TYPE_CHANGE;

                        $this->player->dataPacket($packet);

                    }
                }
            }

            $this->remove();
            $this->send();
        }
    }

    public function resendAll(array $lines) : void {

        if($this->sent === true) {

            $this->lines = $lines;

            $build = $this->buildEntries();

            $keys = array_keys($this->lines);

            for($i = 0; $i < count($build); $i++) {

                $entry = $build[$i];

                $line = $this->lines[$keys[$i]];

                $packet = new SetScorePacket();

                $packet->entries[] = $entry;

                $packet->type = SetScorePacket::TYPE_REMOVE;

                $this->player->dataPacket($packet);

                if($line->isHidden() === false) {

                    $packet = new SetScorePacket();

                    $packet->entries[] = $entry;

                    $packet->type = SetScorePacket::TYPE_CHANGE;

                    $this->player->dataPacket($packet);

                }
            }
        }

        $this->remove();
        $this->send();
    }

    /**
     * @return ScoreboardLine[]
     */
    public function getLines() : array {
        return $this->lines;
    }

    public function updateLine($key, array $values) : self {

        $keys = array_keys($this->lines);

        foreach($keys as $thekey) {
            $value = $this->lines[$thekey];
            if($value instanceof DataLine) {
                if($key === $thekey) {
                    $this->lines[$key] = $value->updateText($values);
                    break;
                }
            }
        }
        return $this;
    }

    /**
     * @return DataPacket[]
     */
    private function build() {

        $packets = [];

        $pkt = new SetDisplayObjectivePacket();
        $pkt->objectiveName = $this->player->getName();
        $pkt->displayName = $this->title;
        $pkt->sortOrder = self::SORT_ASCENDING;
        $pkt->displaySlot = self::SLOT_SIDEBAR;
        $pkt->criteriaName = "dummy";

        $packets[] = clone $pkt;

        $setScorePkt = new SetScorePacket();
        $setScorePkt->type = SetScorePacket::TYPE_CHANGE;
        $setScorePkt->entries = $this->buildEntries();
        $packets[] = clone $setScorePkt;
        return $packets;
    }

    /**
     * @return ScorePacketEntry[]
     */
    private function buildEntries() {

        $lines = [];

        $dataLines = [];

        $keys = array_keys($this->lines);

        foreach($keys as $key) {
            $value = $this->lines[$key];
            if($value instanceof DataLine) {
                $text = $value->getText();
                $dataLines[] = PracticeUtil::str_replace($text, ["%" => ""]);
            }
        }

        $baseLineSeparatorText = PracticeUtil::getLineSeparator($dataLines);

        //$len = strlen($baseLineSeparatorText);

        if($this->deviceOS === PracticeUtil::WINDOWS_10) $baseLineSeparatorText = $baseLineSeparatorText . PracticeUtil::WIN10_ADDED_SEPARATOR;

        $keys = array_keys($this->lines);

        foreach($keys as $key) {
            $value = $this->lines[$key];
            if($value instanceof SeparatorLine) {
                $text = $baseLineSeparatorText;
                if($value->isVisible() === false) $text = PracticeUtil::str_replace($text, ["-" => " "]);
                $value = $value->editText($text);
                $this->lines[$key] = $value;
            }
        }

        $keys = array_keys($this->lines);

        foreach($keys as $key) {

            $value = $this->lines[$key];

            if($value->isHidden() === false) {

                $id = $value->getId();

                $text = $value->getText();

                $entry = new ScorePacketEntry();

                $entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;

                $entry->objectiveName = $this->player->getName();

                $entry->entityUniqueId = $this->player->getId();

                $entry->scoreboardId = $id;

                $entry->customName = $text;

                $entry->score = $id;

                $lines[] = $entry;
            }
        }

        return $lines;
    }
}