<?php

declare(strict_types=1);

namespace practice\games\duels\player;


use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\UUID;
use practice\games\duels\player\properties\IDuelPlayerProperty;
use practice\games\duels\player\properties\PropertyInfo;
use practice\games\duels\player\properties\types\FloatDPProperty;
use practice\games\duels\player\properties\types\IntegerDPProperty;
use practice\player\PracticePlayer;

class DuelPlayer
{

    const NUM_HITS = "property.num.hits";
    const PLAYER_HEALTH = "property.player.health";

    /** @var PropertyInfo[] */
    private static $registeredProperties = [];

    /**
     * Initialized the registered properties to the player.
     */
    public static function init(): void
    {
        self::registerProperty(self::NUM_HITS, "Num-Hits: {VALUE}", IntegerDPProperty::class, "The number of hits you have landed.");
        self::registerProperty(self::PLAYER_HEALTH, "Health: {VALUE}", FloatDPProperty::class);
    }


    /**
     * @param string $localized
     * @param $propertyClass - The corresponding property class, must be a DuelPlayerProperty.
     * @param string $display - Shows how the property is displayed, must contain {VALUE}.
     *        EX: "Num-Hits: {VALUE}"
     * @param string $description - The description of the property.
     * @param bool $override - Determines whether to override if a property exists.
     *
     * Registers the property to the properties list.
     */
    public static function registerProperty(string $localized, string $display, $propertyClass, string $description = "", bool $override = false): void
    {
        if(!$override && isset(self::$registeredProperties[$localized]))
        {
            return;
        }

        if(!is_subclass_of($propertyClass, IDuelPlayerProperty::class))
        {
            return;
        }

        self::$registeredProperties[$localized] = new PropertyInfo($localized, $display, $propertyClass, $description);
    }

    /**
     * @param string $name
     * @param Item $item
     * @param mixed $defaultValue
     * @return IDuelPlayerProperty|null
     *
     * Creates the property instance based on the property name.
     */
    public function createPropertyInstance(string $name, Item $item, $defaultValue = null): ?IDuelPlayerProperty
    {
        if(!isset(self::$registeredProperties[$name]))
        {
            return null;
        }

        $info = self::$registeredProperties[$name];
        return $info->convertToInstance($item, $defaultValue);
    }

    // ---------------------------------- Duel Player Property Instance ---------------------------------

    /** @var IDuelPlayerProperty */
    protected $properties;
    /** @var Player|PracticePlayer */
    protected $player;

    /** @var UUID */
    private $serverID;
    /** @var bool */
    private $pe;

    /** @var bool */
    private $teleportedToSpawn = false, $online = true;

    public function __construct(PracticePlayer $player)
    {
        $this->player = $player;
        $this->serverID = $player->getServerID();

        $this->properties = $properties ?? [];
        $this->pe = $player->getClientInfo()->isPE();

        $this->initProperties();
    }

    /**
     * Initializes the properties & adds them to the list.
     */
    protected function initProperties(): void
    {
        // Initializes the properties to the list.
        $this->addProperty(self::createPropertyInstance(
            self::NUM_HITS, Item::get(Item::STEAK), 0));

        $this->addProperty(self::createPropertyInstance(
            self::PLAYER_HEALTH, Item::get(Item::POTION), $this->player->getHealth()));
    }

    /**
     * @param $property
     *
     * Adds the property to the player.
     */
    protected function addProperty($property): void
    {
        if(!$property instanceof IDuelPlayerProperty)
        {
            return;
        }

        $this->properties[$property->getLocalized()] = $property;
    }

    /**
     * @param string $property
     * @return IDuelPlayerProperty|null
     *
     * Gets a tracking property of the player.
     */
    public function getProperty(string $property): ?IDuelPlayerProperty
    {
        if(isset($this->properties[$property]))
        {
            return $this->properties[$property];
        }

        return null;
    }

    /**
     * @return array|IDuelPlayerProperty
     *
     * Gets all of the duel properties.
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return Player|PracticePlayer
     *
     * Gets the player.
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * @return bool
     *
     * Determines whether the player is pe.
     */
    public function isPE(): bool
    {
        return $this->pe;
    }

    /**
     * @return UUID
     *
     * Gets the server id of the player.
     */
    public function getServerID(): UUID
    {
        return $this->serverID;
    }

    /**
     * @param bool $rawCheck - Determines whether to check if its a raw check or not.
     * @return bool
     *
     * Determines if the player is online or not.
     */
    public function isOnline(bool $rawCheck = false): bool
    {
        if($rawCheck)
        {
            return $this->player->isOnline();
        }

        return $this->player->isOnline() && $this->online;
    }

    /**
     * Sets the player as offline.
     */
    public function setOffline(): void
    {
       $this->online = false;
    }

    /**
     * Sets the player as teleported to spawn, used to prevent death glitches.
     * Overriden duel player classes should not change this.
     */
    public function setTeleportedToSpawn(): void
    {
        $this->teleportedToSpawn = true;
    }

    /**
     * @return bool
     *
     * Determines whether or not the player had already teleported to spawn,
     * used to prevent death glitches.
     */
    public function teleportedToSpawn(): bool
    {
        return $this->teleportedToSpawn;
    }

    /**
     * @param $player
     * @return bool
     *
     * Determines if the player is equivalent to the next.
     */
    public function equals($player): bool
    {
        if($player instanceof PracticePlayer)
        {
            return $player->getServerID()->equals($this->serverID)
                && $player->getName() === $this->player->getName();
        }
        elseif ($player instanceof DuelPlayer && $player->isOnline())
        {
            return $this->equals($player->getPlayer());
        }

        return false;
    }
}