<?php

declare(strict_types=1);

namespace jkorn\practice;


use jkorn\practice\commands\PracticeCommand;
use jkorn\practice\level\gen\PracticeChunkLoader;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use jkorn\practice\player\PracticePlayer;

class PracticeUtil
{

    // Color array constant.
    const COLOR_ARRAY = [
        "{BLUE}" => TextFormat::BLUE,
        "{GREEN}" => TextFormat::GREEN,
        "{RED}" => TextFormat::RED,
        "{DARK_RED}" => TextFormat::DARK_RED,
        "{DARK_BLUE}" => TextFormat::DARK_BLUE,
        "{DARK_AQUA}" => TextFormat::DARK_AQUA,
        "{DARK_GREEN}" => TextFormat::DARK_GREEN,
        "{GOLD}" => TextFormat::GOLD,
        "{GRAY}" => TextFormat::GRAY,
        "{DARK_GRAY}" => TextFormat::DARK_GRAY,
        "{DARK_PURPLE}" => TextFormat::DARK_PURPLE,
        "{LIGHT_PURPLE}" => TextFormat::LIGHT_PURPLE,
        "{RESET}" => TextFormat::RESET,
        "{YELLOW}" => TextFormat::YELLOW,
        "{AQUA}" => TextFormat::AQUA,
        "{BOLD}" => TextFormat::BOLD,
        "{WHITE}" => TextFormat::WHITE,
        "{ITALIC}" => TextFormat::ITALIC,
        "{UNDERLINE}" => TextFormat::UNDERLINE
    ];

    const SWISH_SOUNDS = [
        LevelSoundEventPacket::SOUND_ATTACK => true,
        LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE => true,
        LevelSoundEventPacket::SOUND_ATTACK_STRONG => true
    ];

    /**
     * @param string|int $index - Int or string.
     * @return int|string
     *
     * Converts the armor index based on its type.
     */
    public static function convertArmorIndex($index)
    {
        if(is_string($index))
        {
            switch(strtolower($index))
            {
                case "boots":
                    return 3;
                case "leggings":
                    return 2;
                case "chestplate":
                case "chest":
                    return 1;
                case "helmet":
                    return 0;
            }

            return 0;
        }

        switch($index % 4)
        {
            case 0:
                return "helmet";
            case 1:
                return "chestplate";
            case 2:
                return "leggings";
            case 3:
                return "boots";
        }

        return 0;
    }

    /**
     * @param Item $item
     * @return array
     *
     * Converts an item to an array.
     */
    public static function itemToArr(Item $item): array
    {
        $output = [
            "id" => $item->getId(),
            "meta" => $item->getDamage(),
            "count" => $item->getCount()
        ];

        if($item->hasEnchantments())
        {
            $enchantments = $item->getEnchantments();
            $inputEnchantments = [];
            foreach($enchantments as $enchantment)
            {
                $inputEnchantments[] = [
                    "id" => $enchantment->getId(),
                    "level" => $enchantment->getLevel()
                ];
            }

            $output["enchants"] = $inputEnchantments;
        }

        if($item->hasCustomName())
        {
            $output["customName"] = $item->getCustomName();
        }

        return $output;
    }

    /**
     * @param array $input
     * @return Item|null
     *
     * Converts an array of data to an item.
     */
    public static function arrToItem(array $input): ?Item
    {
        if(!isset($input["id"], $input["meta"], $input["count"]))
        {
            return null;
        }

        $item = Item::get($input["id"], $input["meta"], $input["count"]);
        if(isset($input["customName"]))
        {
            $item->setCustomName($input["customName"]);
        }

        if(isset($input["enchants"]))
        {
            $enchantments = $input["enchants"];
            foreach($enchantments as $enchantment)
            {
                if(!isset($enchantment["id"], $enchantment["level"]))
                {
                    continue;
                }

                $item->addEnchantment(new EnchantmentInstance(
                    Enchantment::getEnchantment($enchantment["id"]),
                    $enchantment["level"]
                ));
            }
        }

        return $item;
    }

    /**
     * @param EffectInstance $instance
     * @param int $duration
     * @return array
     *
     * Converts an effect instance to an array.
     */
    public static function effectToArr(EffectInstance $instance, int $duration = 30 * 60 * 20): array
    {
        return [
            "id" => $instance->getId(),
            "amplifier" => $instance->getAmplifier(),
            "duration" => $duration
        ];
    }

    /**
     * @param array $input
     * @return EffectInstance|null
     *
     * Converts an array to an effect instance.
     */
    public static function arrToEffect(array $input): ?EffectInstance
    {
        if(!isset($input["id"], $input["amplifier"], $input["duration"]))
        {
            return null;
        }

        return new EffectInstance(
            Effect::getEffect($input["id"]),
            $input["duration"],
            $input["amplifier"]
        );
    }

    /**
     * @param string $message - The address to the message.
     *
     * Converts the message according to its colors.
     */
    public static function convertMessageColors(string &$message): void
    {
        foreach(self::COLOR_ARRAY as $color => $value)
        {
            if(strpos($message, $color) !== false)
            {
                $message = str_replace($color, $value, $message);
            }
        }
    }

    /**
     * @param $level1 - The first level.
     * @param $level2 - The second level.
     * @return bool - Return true if equivalent, false otherwise.
     *
     * Determines if the levels are equivalent.
     */
    public static function areLevelsEqual($level1, $level2): bool
    {
        if(!$level1 instanceof Level && !is_string($level1))
        {
            return false;
        }

        if(!$level2 instanceof Level && !is_string($level2))
        {
            return false;
        }

        if($level1 instanceof Level && $level2 instanceof Level)
        {
            return $level1->getId() === $level2->getId();
        }

        $level2Name = $level2 instanceof Level ? $level2->getName() : $level2;
        $level1Name = $level1 instanceof Level ? $level1->getName() : $level1;

        return $level1Name === $level2Name;
    }

    /**
     * @param Vector3|null $vec3 - The input vector3.
     * @return array|null
     *
     * Converts the vector3 to an array.
     */
    public static function vec3ToArr(?Vector3 $vec3): ?array
    {
        if($vec3 == null)
        {
            return null;
        }

        $output = [
            "x" => $vec3->x,
            "y" => $vec3->y,
            "z" => $vec3->z,
        ];

        if($vec3 instanceof Location)
        {
            $output["pitch"] = $vec3->pitch;
            $output["yaw"] = $vec3->yaw;
        }

        return $output;
    }

    /**
     * @param $input - The input array.
     * @return Vector3|null
     *
     * Converts an array input to a Vector3.
     */
    public static function arrToVec3($input): ?Vector3
    {
        if(is_array($input) && isset($input["x"], $input["y"], $input["z"]))
        {
            if(isset($input["pitch"], $input["yaw"]))
            {
                return new Location($input["x"], $input["y"], $input["z"], $input["yaw"], $input["pitch"]);
            }

            return new Vector3($input["x"], $input["y"], $input["z"]);
        }

        return null;
    }

    /**
     * @param string $uuid
     * @return Player|null
     *
     * Gets the player from their server id.
     */
    public static function getPlayerFromServerID(string $uuid): ?Player
    {
        $players = Server::getInstance()->getOnlinePlayers();

        foreach($players as $player)
        {
            if(!$player instanceof PracticePlayer)
            {
                continue;
            }

            $pUUID = $player->getServerID();
            if($pUUID->toString() === $uuid)
            {
                return $player;
            }
        }

        return null;
    }

    /**
     * Reloads the players, used in case of a reload, restart, etc...
     */
    public static function reloadPlayers(): void
    {
        $players = Server::getInstance()->getOnlinePlayers();
        foreach($players as $player)
        {
            if($player instanceof PracticePlayer)
            {
                // Sets the player as unsaved, used so player can be saved again.
                $player->setSaved(false);

                // TODO: What other functions could be used here.
            }
        }
    }

    /**
     * @param $level - The level to be deleted.
     * @param $async - Determines whether to delete it async or not.
     *
     * Deletes the level.
     */
    public static function deleteLevel($level, bool $async = false): void
    {
        $server = Server::getInstance();

        if(is_string($level))
        {
            $path = $server->getDataPath() . "worlds/" . $level;
        }
        elseif ($level instanceof Level)
        {
            $server->unloadLevel($level);

            $path = $server->getDataPath() . "worlds/" . $level->getFolderName();
        }

        if(!isset($path))
        {
            return;
        }

        if(!$async)
        {
            self::removeDirectory($path);
            return;
        }

        $server->getAsyncPool()->submitTask(new class($path) extends AsyncTask
        {
            /** @var string */
            private $path;

            public function __construct(string $path)
            {
                $this->path = $path;
            }

            /**
             * Actions to execute when run
             *
             * @return void
             */
            public function onRun()
            {
                $this->removeDirectory($this->path);
            }

            /**
             * @param string $path
             *
             * Removes the directory based on the path name -> same function used in practice util.
             */
            private function removeDirectory(string $path): void
            {
                if(!is_dir($path))
                {
                    return;
                }

                if(substr($path, strlen($path) - 1, 1) != '/')
                {
                    $path .= "/";
                }

                $files = glob($path . "*", GLOB_MARK);
                foreach($files as $file)
                {
                    if(is_dir($file))
                    {
                        self::removeDirectory($file);
                    }
                    else
                    {
                        unlink($file);
                    }
                }
                rmdir($path);
            }
        });
    }

    /**
     * @param string $path - The path name.
     *
     * Removes the directory based on the input path.
     */
    public static function removeDirectory(string $path): void
    {
        if(!is_dir($path))
        {
            return;
        }

        if(substr($path, strlen($path) - 1, 1) != '/')
        {
            $path .= "/";
        }

        $files = glob($path . "*", GLOB_MARK);
        foreach($files as $file)
        {
            if(is_dir($file))
            {
                self::removeDirectory($file);
            }
            else
            {
                unlink($file);
            }
        }
        rmdir($path);
    }

    /**
     * @param PracticeCommand $command
     *
     * Registers the command to the command map.
     */
    public static function registerCommand(PracticeCommand $command): void
    {
        Server::getInstance()->getCommandMap()->register($command->getName(), $command);
    }

    /**
     * @return Position
     *
     * Gets the lobby spawn position.
     */
    public static function getLobbySpawn(): Position
    {
        $level = Server::getInstance()->getDefaultLevel();
        return $level->getSpawnLocation();
    }

    /**
     * @param Level $level - The level.
     * @param int $x - The x coord.
     * @param int $z - The y coord.
     * @param callable $callable - The callback function
     *
     * Chunk loader that helps when a player first loads a chunk -> usually used after teleporting to a
     * newly generated level.
     */
    public static function onChunkGenerated(Level $level, int $x, int $z, callable $callable): void
    {
        if($level->isChunkPopulated($x, $z))
        {
            $callable();
            return;
        }

        $level->registerChunkLoader(new PracticeChunkLoader($level, $x, $z, $callable), $x, $z, true);
    }


    /**
     * @param PracticePlayer $inPlayer - The input player.
     * @param DataPacket $packet
     * @param callable|null $callable
     * @param PracticePlayer[]|null $viewers
     *
     * Broadcasts a packet to a group of viewers based on the position.
     */
    public static function broadcastPacketToViewers(PracticePlayer $inPlayer, DataPacket $packet, ?callable $callable = null, ?array $viewers = null): void
    {
        // Gets the viewers.
        $viewers = $viewers ?? $inPlayer->getLevelNonNull()->getViewersForPosition($inPlayer->asVector3());

        foreach($viewers as $viewer)
        {
            if($viewer->isOnline())
            {
                if(
                    $callable !== null
                    && !$callable($viewer, $packet)
                )
                {
                    continue;
                }

                $viewer->batchDataPacket($packet);
            }
        }
    }
}