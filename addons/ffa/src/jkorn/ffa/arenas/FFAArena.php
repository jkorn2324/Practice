<?php

declare(strict_types=1);

namespace jkorn\ffa\arenas;


use jkorn\ffa\scoreboards\FFAScoreboardManager;
use jkorn\practice\arenas\SavedPracticeArena;
use jkorn\practice\forms\types\properties\ButtonTexture;
use jkorn\practice\kits\IKit;
use jkorn\practice\kits\SavedKit;
use jkorn\practice\level\SpawnArea;
use jkorn\practice\player\PracticePlayer;
use jkorn\practice\PracticeCore;
use jkorn\practice\PracticeUtil;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;

/**
 * Class FFAArena
 * @package jkorn\ffa\arenas
 *
 * The FFA Arena class.
 */
class FFAArena extends SavedPracticeArena
{

    /** @var IKit|null */
    private $kit;
    /** @var SpawnArea */
    private $spawnArea;

    /** @var ButtonTexture|null */
    private $buttonTexture;

    public function __construct(string $name, Level $level, SpawnArea $spawnArea, ?IKit $kit = null, ?ButtonTexture $texture = null)
    {
        parent::__construct($name, $level);

        $this->kit = $kit;
        $this->spawnArea = $spawnArea;

        // Sets the button texture of the ffa arena.
        $this->buttonTexture = $texture;
    }

    /**
     * @param IKit|null $kit
     *
     * Sets the kit of the ffa arena.
     */
    public function setKit(?IKit $kit): void
    {
        $this->kit = $kit;
    }

    /**
     * @return IKit|null
     *
     * Gets the kit.
     */
    public function getKit(): ?IKit
    {
        return $this->kit;
    }

    /**
     * @param Player $player
     *
     * Teleports the player to the ffa arena.
     */
    public function teleportTo(Player $player): void
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

        $scoreboardData = $player->getScoreboardData();
        if($scoreboardData !== null)
        {
            $scoreboardData->setScoreboard(FFAScoreboardManager::FFA_SCOREBOARD);
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
        if($this->kit instanceof SavedKit)
        {
            $kitInfo = $this->kit->getName();
        }
        else
        {
            $kitInfo = null;
        }

        return [
            "kit" => $kitInfo,
            "spawn" => $this->spawnArea->export(),
            "level" => $this->level->getName(),
            "texture" => $this->buttonTexture !== null ? $this->buttonTexture->export() : null
        ];
    }

    /**
     * @return ButtonTexture|null
     *
     * Gets the form button texture of the ffa arena.
     */
    public function getFormButtonTexture(): ?ButtonTexture
    {
        if($this->buttonTexture === null)
        {
            if($this->kit !== null)
            {
                $texture = $this->kit->getFormButtonTexture();
                if($texture !== null)
                {
                    return $texture;
                }
            }
            return new ButtonTexture(ButtonTexture::TYPE_PATH, "textures/ui/deop.png");
        }
        return $this->buttonTexture;
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
        if(isset($data["kit"], $data["spawn"], $data["level"]))
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
                $texture = null;
                if(isset($data["texture"]))
                {
                    $texture = ButtonTexture::decode($data["texture"]);
                }
                return new FFAArena($name, $level, $spawn, $kit, $texture);
            }
        }

        return null;
    }
}