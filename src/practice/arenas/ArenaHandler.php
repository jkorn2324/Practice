<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-21
 * Time: 14:14
 */

declare(strict_types=1);

namespace practice\arenas;

use pocketmine\level\Position;
use pocketmine\utils\Config;
use practice\kits\Kit;
use practice\player\PracticePlayer;
use practice\PracticeCore;
use practice\PracticeUtil;

class ArenaHandler
{

    private $configPath;

    /* @var Config */
    private $config;

    private $closedArenas;

    /* @var DuelArena[]|array */
    private $duelArenas;

    /* @var FFAArena[]|array */
    private $ffaArenas;

    public function __construct() {

        $this->configPath = PracticeCore::getInstance()->getDataFolder() . "/arenas.yml";
        $this->initConfig();

        $this->closedArenas = [];

        $this->initArenas();

        $combined_arrays = array_merge($this->getFFAArenas(), $this->getDuelArenas());

        foreach($combined_arrays as $value) {
            if($value instanceof PracticeArena) {
                $name = $value->getName();
                $this->closedArenas[$name] = false;
            }
        }
    }

    private function initConfig() : void {

        $this->config = new Config($this->configPath, Config::YAML, array());

        $edited = false;

        if(!$this->config->exists("duel-arenas")) {
            $this->config->set("duel-arenas", []);
            $edited = true;
        }

        if(!$this->config->exists("ffa-arenas")) {
            $this->config->set("ffa-arenas", []);
            $edited = true;
        }

        if($edited === true) $this->config->save();
    }

    private function initArenas() : void {

        $this->ffaArenas = [];
        $this->duelArenas = [];

        $ffaKeys = $this->getConfig()->get("ffa-arenas");

        $ffaArenaKeys = array_keys($ffaKeys);

        foreach($ffaArenaKeys as $key) {
            $key = strval($key);
            if($this->isFFAArenaFromConfig($key)) {
                $arena = $this->getFFAArenaFromConfig($key);
                $this->ffaArenas[$key] = $arena;
            }
        }

        $duelKeys = $this->getConfig()->get("duel-arenas");

        $duelArenaKeys = array_keys($duelKeys);

        foreach($duelArenaKeys as $key) {
            $key = strval($key);
            if($this->isDuelArenaFromConfig($key)){
                $arena = $this->getDuelArenaFromConfig($key);
                $this->duelArenas[$key] = $arena;
            }
        }
    }

    public function createArena(string $name, Position $pos, string $arenaType = PracticeArena::FFA_ARENA) {

        if($arenaType === PracticeArena::DUEL_ARENA) $this->createDuelArena($name, $pos);
        elseif ($arenaType === PracticeArena::FFA_ARENA) $this->createFFAArena($name, $pos);

        $this->closedArenas[$name] = false;
    }

    private function createDuelArena(string $name, Position $pos) : void {

        $arena = new DuelArena($name, false, $pos);

        $this->duelArenas[$name] = $arena;

        $map = $arena->toMap();
        $duelArenas = $this->getConfig()->get("duel-arenas");

        if(!isset($duelArenas[$name])) {
            $duelArenas[$name] = $map;
            $this->getConfig()->set("duel-arenas", $duelArenas);
            //$this->getConfig()->set("ffa-arenas", $this->getConfig()->get("ffa-arenas"));
            $this->getConfig()->save();
        }

        /*if(!key_exists($name, $duelArenas)) {

        }*/
    }

    private function createFFAArena(string $name, Position $pos) : void {

        $arena = new FFAArena($name, false, $pos);

        $this->ffaArenas[$name] = $arena;

        $map = $arena->toMap();
        $ffaArenas = $this->getConfig()->get("ffa-arenas");
        if(!isset($ffaArenas[$name])) {
            $ffaArenas[$name] = $map;
            //$this->getConfig()->set("duel-arenas", $this->getConfig()->get("duel-arenas"));
            $this->getConfig()->set("ffa-arenas", $ffaArenas);
            $this->getConfig()->save();
        }
    }

    public function removeArena(string $name) : bool {

        $result = false;

        if($this->isDuelArena($name)) {
            $this->removeDuelArena($name);
            $result = true;
        } elseif ($this->isFFAArena($name)) {
            $this->removeFFAArena($name);
            $result = true;
        }

        if($result === true) {
            if(isset($this->closedArenas[$name]))
                unset($this->closedArenas[$name]);
        }

        return $result;
    }

    private function removeDuelArena(string $name) : void {

        $duelArenas = $this->getConfig()->get("duel-arenas");

        if(isset($duelArenas[$name])) {

            unset($duelArenas[$name], $this->duelArenas[$name]);
            $this->getConfig()->set("duel-arenas", $duelArenas);
            //$this->getConfig()->set("ffa-arenas", $this->getConfig()->get("ffa-arenas"));
            $this->getConfig()->save();
        }
    }

    private function removeFFAArena(string $name) : void {

        $ffaArenas = $this->getConfig()->get("ffa-arenas");

        if(isset($ffaArenas[$name])) {

            unset($ffaArenas[$name], $this->ffaArenas[$name]);
            //$this->getConfig()->set("duel-arenas", $this->getConfig()->get("duel-arenas"));
            $this->getConfig()->set("ffa-arenas", $ffaArenas);
            $this->getConfig()->save();
        }
    }

    public function updateArena(string $name, PracticeArena $arena) : bool {

        $result = false;

        if($arena->getArenaType() === PracticeArena::FFA_ARENA) {
            if($this->isFFAArena($name)) {
                $result = true;
                $ffaArenas = $this->getConfig()->get("ffa-arenas");

                $this->ffaArenas[$name] = $arena;

                $map = $arena->toMap();
                $ffaArenas[$name] = $map;
                //$this->getConfig()->set("duel-arenas", $this->getConfig()->get("duel-arenas"));
                $this->getConfig()->set("ffa-arenas", $ffaArenas);
                $this->getConfig()->save();
            }
        } else if ($arena->getArenaType() === PracticeArena::DUEL_ARENA) {
            if($this->isDuelArena($name)) {
                $result = true;

                $this->duelArenas[$name] = $arena;

                $duelArenas = $this->getConfig()->get("duel-arenas");
                $map = $arena->toMap();
                $duelArenas[$name] = $map;
                $this->getConfig()->set("duel-arenas", $duelArenas);
                //$this->getConfig()->set("ffa-arenas", $this->getConfig()->get("ffa-arenas"));
                $this->getConfig()->save();
            }
        }

        return $result;
    }

    private function getConfig() : Config {
        return $this->config;
    }

    public function getFFAArena(string $name) {

        $result = null;

        if(isset($this->ffaArenas[$name]))
            $result = $this->ffaArenas[$name];

        return $result;
    }

    private function getFFAArenaFromConfig(string $name) {

        $ffaArenas = $this->getConfig()->get("ffa-arenas");

        $result = null;

        if(isset($ffaArenas[$name])) {

            $arena = $ffaArenas[$name];
            $arenaKit = Kit::NO_KIT;
            $arenaBuild = false;
            $arenaSpawn = null;

            $foundArena = false;

            if(PracticeUtil::arr_contains_keys($arena, "build", "spawn", "level", "kit")) {

                $kit = $arena["kit"];
                $canBuild = $arena["build"];
                $spawnArr = $arena["spawn"];
                $level = $arena["level"];

                $arenaSpawn = null;

                if(PracticeUtil::isALevel($level))
                    $arenaSpawn = PracticeUtil::getPositionFromMap($spawnArr, $level);
                elseif (PracticeUtil::isALevel($level, false)) {
                    PracticeUtil::loadLevel($level);
                    $arenaSpawn = PracticeUtil::getPositionFromMap($spawnArr, $level);
                }

                $arenaBuild = boolval($canBuild);

                if($kit !== Kit::NO_KIT) $arenaKit = strval($kit);

                if(!is_null($arenaSpawn)) $foundArena = true;
            }

            if($foundArena)
                $result = new FFAArena($name, $arenaBuild, $arenaSpawn, $arenaKit);
        }
        return $result;
    }

    private function isFFAArenaFromConfig(string $name) : bool {
        return !is_null($this->getFFAArenaFromConfig($name));
    }

    public function isFFAArena(string $name) : bool {
        return isset($this->ffaArenas[$name]);
    }

    public function getDuelArena(string $name) {
        $result = null;

        if(isset($this->duelArenas[$name]))
            $result = $this->duelArenas[$name];

        return $result;
    }

    private function getDuelArenaFromConfig(string $name) {

        $duelArenas = $this->getConfig()->get("duel-arenas");
        $result = null;

        if(isset($duelArenas[$name])) {

            $arena = $duelArenas[$name];
            $arenaCenter = null;
            $playerPos = null;
            $oppPos = null;
            $build = false;
            $kits = [];

            $foundArena = false;

            if(PracticeUtil::arr_contains_keys($arena, "center", "build", "level", "player-pos", "opponent-pos", "kits")) {

                $cfgKits = $arena["kits"];
                $canBuild = $arena["build"];
                $centerArr = $arena["center"];
                $level = $arena["level"];
                $cfgPlayerPos = $arena["player-pos"];
                $cfgOppPos = $arena["opponent-pos"];

                if(!is_null($cfgKits)) {
                    if(is_array($cfgKits)) {
                        foreach($cfgKits as $kit) {
                            if($kit !== Kit::NO_KIT) {
                                $kits[] = strval($kit);
                            }
                        }
                    } elseif (is_string($cfgKits)) {
                        $k = $cfgKits;
                        if($k !== Kit::NO_KIT) {
                            $kits[] = $k;
                        }
                    }
                }

                $arenaCenter = PracticeUtil::getPositionFromMap($centerArr, $level);
                $playerPos = PracticeUtil::getPositionFromMap($cfgPlayerPos, $level);
                $oppPos = PracticeUtil::getPositionFromMap($cfgOppPos, $level);

                if(is_bool($canBuild)) $build = $canBuild;

                if(!is_null($arenaCenter) and !is_null($playerPos) and !is_null($oppPos)) $foundArena = true;
            }

            if($foundArena) {
                $result = new DuelArena($name, $build, $arenaCenter, $kits, $playerPos, $oppPos);
            }
        }
        return $result;
    }

    private function isDuelArenaFromConfig(string $name) : bool {
        return !is_null($this->getDuelArenaFromConfig($name));
    }

    public function isDuelArena(string $name) : bool {
        return isset($this->duelArenas[$name]);
    }

    public function getArena(string $name) {
        $result = null;
        if($this->isDuelArena($name)) {
            $result = $this->getDuelArena($name);
        } elseif ($this->isFFAArena($name)) {
            $result = $this->getFFAArena($name);
        }
        return $result;
    }

    public function getArenaClosestTo(Position $pos) {

        $arenas = array_merge($this->getDuelArenas(), $this->getFFAArenas());

        $greatest = null;

        $closestDistance = -1.0;

        if(!is_null($pos)) {

            foreach ($arenas as $arena) {

                if ($arena instanceof PracticeArena) {
                    $posLevel = $pos->getLevel();
                    $arenaLevel = $arena->getLevel();

                    if (PracticeUtil::areLevelsEqual($posLevel, $arenaLevel)) {
                        $center = $arena->getSpawnPosition();
                        $currentDistance = $center->distance($pos);
                        if ($closestDistance === -1.0) {
                            $closestDistance = $currentDistance;
                            $greatest = $arena;
                        } else {
                            if ($currentDistance < $closestDistance) {
                                $closestDistance = $currentDistance;
                                $greatest = $arena;
                            }
                        }
                    }
                }
            }
        }

        return $greatest;
    }

    public function doesArenaExist(string $name) : bool {
        return $this->isDuelArena($name) or $this->isFFAArena($name);
    }

    public function getArenaList(bool $listAll) : string {

        $result = "Arena list: ";

        $duelArenas = $this->getDuelArenas();
        $ffaArenas = $this->getFFAArenas();

        $allArenas = ($listAll === true) ? array_merge($ffaArenas, $duelArenas) : $ffaArenas;

        $len = count($allArenas) - 1;
        $count = 0;

        foreach($allArenas as $arena) {

            if(!is_null($arena) and $arena instanceof PracticeArena) {

                $comma = ($count === $len ? "" : ", ");

                $arenaType = PracticeArena::getFormattedType($arena->getArenaType());

                $result .= $arena->getName() . " " . $arenaType . $comma;
            }
            $count++;
        }

        return $result;
    }

    public function setArenaClosed($arena) : void {

        $name = null;
        if(isset($arena) and !is_null($arena)) {
            if($arena instanceof PracticeArena) {
                $name = $arena->getName();
            } elseif (is_string($arena)) {
                $name = $arena;
            }
        }

        if(!is_null($name)) {
            if(!$this->isArenaClosed($name))
                $this->closedArenas[$name] = true;
        }
    }

    public function isArenaClosed(string $arena) : bool {
        return isset($this->closedArenas[$arena]) and $this->closedArenas[$arena] === true;
    }

    public function setArenaOpen($arena) : void {

        $name = null;

        if(isset($arena) and !is_null($arena)) {
            if($arena instanceof PracticeArena) {
                $name = $arena->getName();
            } elseif (is_string($arena)) {
                $name = $arena;
            }
        }

        if(!is_null($name)) {
            if($this->isArenaClosed($name)) {
                $this->closedArenas[$name] = false;
            }
        }
    }

    public function getNumPlayersInArena(string $arena) : int {
        $result = 0;
        $players = PracticeCore::getPlayerHandler()->getOnlinePlayers();
        foreach($players as $p) {
            if($p instanceof PracticePlayer and $p->isInArena()) {
                $a = $p->getCurrentArena();
                $name = $a->getName();
                if($name === $arena) $result++;
            }
        }
        return $result;
    }


    /**
     * @return DuelArena[]
     */
    public function getDuelArenas() : array {
        return $this->duelArenas;
    }

    /**
     * @return FFAArena[]
     */
    public function getFFAArenas() : array {
        return $this->ffaArenas;
    }
}