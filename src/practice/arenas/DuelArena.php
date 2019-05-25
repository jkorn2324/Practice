<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-21
 * Time: 13:37
 */

namespace practice\arenas;


use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\plugin\ScriptPluginLoader;
use practice\kits\Kit;
use practice\PracticeUtil;

class DuelArena extends PracticeArena
{
    
    private $playerPos;

    private $opponentPos;

    private $playerPitch;
    private $playerYaw;
    
    private $oppPitch;
    private $oppYaw;

    public function __construct(string $name, bool $canBuild, Position $center, $kits = null, Position $playerPos = null, Position $oppPos = null)
    {
        parent::__construct($name, self::DUEL_ARENA, $canBuild, $center);

        $this->playerPos = new Position($center->x - 6, $center->y, $center->z, $center->level);
        $this->opponentPos = new Position($center->x + 6, $center->y, $center->z, $center->level);
        $this->playerPitch = null;
        $this->playerYaw = null;
        $this->oppYaw = null;
        $this->oppPitch = null;

        if(!is_null($playerPos)) {
            $this->playerPos = $playerPos;
            if($playerPos instanceof Location) {
                $this->playerPitch = $playerPos->pitch;
                $this->playerYaw = $playerPos->yaw;
            }
        }

        if(!is_null($oppPos)) {
            $this->opponentPos = $oppPos;
            if($oppPos instanceof Location) {
                $this->oppPitch = $oppPos->pitch;
                $this->oppYaw = $oppPos->yaw;
            }
        }

        if(!is_null($kits)) {
            if($kits instanceof Kit) {
                $this->kits[] = $kits->getName();
            } elseif (is_array($kits)) {

                $this->kits = [];
                $keys = array_keys($kits);

                foreach($keys as $key) {
                    $val = $kits[$key];
                    if($val instanceof Kit) {
                        $this->kits[] = $val->getName();
                    } elseif (is_string($val)){
                        $this->kits[] = $val;
                    }
                }
            } elseif (is_string($kits)) {
                $this->kits[] = $kits;
            }
        }
    }

    public function getKits() : array {
        return $this->kits;
    }

    public function setPlayerPos($pos) : DuelArena {
        if($pos instanceof Position) {
            if(PracticeUtil::areLevelsEqual($pos->level, $this->level)){
                $this->playerPos = $pos;
            }
        } elseif ($pos instanceof Location) {
            if(PracticeUtil::areLevelsEqual($pos->level, $this->level)) {
                $this->playerPos = new Position($pos->x, $pos->y, $pos->z, $pos->level);
                $this->playerYaw = $pos->yaw;
                $this->playerPitch = $pos->pitch;
            }
        }
        return $this;
    }

    public function setOpponentPos($pos) : DuelArena {
        if($pos instanceof Position) {
            if(PracticeUtil::areLevelsEqual($pos->level, $this->level)){
                $this->opponentPos = $pos;
            }
        } elseif ($pos instanceof Location) {
            if(PracticeUtil::areLevelsEqual($pos->level, $this->level)) {
                $this->opponentPos = new Position($pos->x, $pos->y, $pos->z, $pos->level);
                $this->oppYaw = $pos->yaw;
                $this->oppPitch = $pos->pitch;
            }
        }
        return $this;
    }

    public function getPlayerPos() {
        $result = $this->playerPos;
        if(!is_null($this->playerYaw) and !is_null($this->playerPitch)) {
            $result = new Location($this->playerPos->x, $this->playerPos->y, $this->playerPos->z, $this->playerYaw, $this->playerPitch, $this->level);
        }
        return $result;
    }

    public function getOpponentPos() {
        $result = $this->opponentPos;
        if(!is_null($this->oppYaw) and !is_null($this->oppPitch)) {
            $result = new Location($this->opponentPos->x, $this->opponentPos->y, $this->opponentPos->z, $this->oppYaw, $this->oppPitch, $this->level);
        }
        return $result;
    }

    public function toMap(): array {
        $result = [
            "center" => PracticeUtil::getPositionToMap($this->getSpawnPosition()),
            "level" => $this->level->getFolderName(),
            "build" => $this->canBuild(),
            "player-pos" => PracticeUtil::getPositionToMap($this->getPlayerPos()),
            "opponent-pos" => PracticeUtil::getPositionToMap($this->getOpponentPos())
        ];

        $kit = null;

        $size = count($this->kits);

        if($size > 0) {
            if($this->getArenaType() === self::DUEL_ARENA) {
                $kit = $this->kits;
            } else {
                $kit = $this->kits[0];
            }
        } else {
            $kit = Kit::NO_KIT;
        }

        if(!is_null($kit)) {
            $result["kits"] = $kit;
        }
        return $result;
    }
}