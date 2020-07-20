<?php

declare(strict_types=1);

namespace jkorn\practice\arenas\types\ffa;


use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
use jkorn\practice\arenas\PracticeArena;
use jkorn\practice\kits\SavedKit;
use jkorn\practice\level\SpawnArea;
use jkorn\practice\misc\ISaved;
use jkorn\practice\player\PracticePlayer;
use jkorn\practice\PracticeCore;
use jkorn\practice\PracticeUtil;
use jkorn\practice\scoreboard\ScoreboardData;

class FFAArena extends PracticeArena implements ISaved
{

    /** @var SavedKit|null */
    private $kit;
    /** @var SpawnArea */
    private $spawnArea;

    /** @var int */
    private $players = 0;

    public function __construct(string $name, Level $level, SpawnArea $spawnArea, ?SavedKit $kit = null)
    {
        parent::__construct($name, $level);
        $this->kit = $kit;

        $this->spawnArea = $spawnArea;
    }

    /**
     * @param SavedKit|null $kit
     *
     * Sets the kit of the ffa arena.
     */
    public function setKit(?SavedKit $kit): void
    {
        $this->kit = $kit;
    }

    /**
     * @return SavedKit|null
     *
     * Gets the kit.
     */
    public function getKit(): ?SavedKit
    {
        return $this->kit;
    }

    /**
     * @return int
     *
     * Gets the number of players in the arena.
     */
    public function getPlayers(): int
    {
        return $this->players;
    }

    /**
     * Decrements the number of players
     * within the arena.
     */
    public function removePlayer(): void
    {
        if(--$this->players < 0)
        {
            $this->players = 0;
        }
    }

    /**
     * @param Player $player
     * @param bool $message
     *
     * Teleports the player to the ffa arena.
     */
    public function teleportTo(Player $player, bool $message = true): void
    {
        if(!$player instanceof PracticePlayer)
        {
            return;
        }
        
        $player->clearInventory();
        if($this->kit !== null)
        {
            $this->kit->sendTo($player, false);
        }

        $spawnPosition = new Position(
            $this->spawnArea->getSpawn()->x,
            $this->spawnArea->getSpawn()->y,
            $this->spawnArea->getSpawn()->z,
            $this->level
        );

        $player->teleport($spawnPosition);
        $player->setGamemode(Player::ADVENTURE);
        $player->removeAllEffects();
        $player->setHealth($player->getMaxHealth());
        $player->setFood($player->getMaxFood());
        $player->setSaturation($player->getMaxSaturation());

        $this->players++;

        $scoreboardData = $player->getScoreboardData();
        if($scoreboardData !== null && $scoreboardData->getScoreboard() !== ScoreboardData::SCOREBOARD_NONE)
        {
            $scoreboardData->setScoreboard(ScoreboardData::SCOREBOARD_FFA);
        }

        if($message)
        {
            // TODO: Send message.
        }
    }

    /**
     * @param Vector3 $position
     * @return bool
     *
     * Determines if the player is within spawn.
     */
    public function isWithinSpawn(Vector3 $position): bool
    {
        if($position instanceof Position)
        {
            $level = $position->getLevel();
            if(!PracticeUtil::areLevelsEqual($level, $this->level))
            {
                return false;
            }
        }

        return $this->spawnArea->isWithinArea($position);
    }

    /**
     * @return array
     *
     * Exports the ffa arena to be stored.
     */
    public function export(): array
    {
        return [
            "kit" => $this->kit instanceof SavedKit ? $this->kit->getName() : null,
            "spawn" => $this->spawnArea->export(),
            "level" => $this->level->getName()
        ];
    }

    /**
     * @param $arena
     * @return bool
     *
     * Determines if two arenas are equivalent.
     */
    public function equals($arena): bool
    {
        if($arena instanceof FFAArena)
        {
            return $arena->getLocalizedName() === $this->localizedName;
        }

        return false;
    }

    /**
     * @param string $name - The name of the arena.
     * @param array $data - The data of the arena.
     * @return FFAArena|null
     *
     * Decodes the FFA Arena from an array of data.
     */
    public static function decode(string $name, array $data): ?FFAArena
    {
        $server = Server::getInstance();
        if(isset($data["kit"], $data["spawn"], $data["level"], $data["area"]))
        {
            $loaded = true;
            if(!$server->isLevelLoaded($data["level"]))
            {
                $loaded = $server->loadLevel($data["level"]);
            }

            // Checks if the level is loaded or not.
            if(!$loaded)
            {
                return null;
            }

            // Make sure kits load before arenas.
            $kit = PracticeCore::getKitManager()->get($data["kit"]);
            $spawn = SpawnArea::decode($data["spawn"]);
            $level = $server->getLevelByName($data["level"]);

            if($spawn !== null && $level !== null)
            {
                return new FFAArena($name, $level, $spawn, $kit);
            }
        }

        return null;
    }
}