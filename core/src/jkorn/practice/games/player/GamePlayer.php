<?php

declare(strict_types=1);

namespace jkorn\practice\games\player;


use jkorn\practice\games\player\properties\GPPropertyInfo;
use jkorn\practice\games\player\properties\IGamePlayerProperty;
use jkorn\practice\player\PracticePlayer;
use pocketmine\item\Item;
use pocketmine\utils\UUID;

abstract class GamePlayer
{

    /** @var GPPropertyInfo[] */
    private static $registeredProperties = [];

    /** @var bool - Sets the properties as initialized */
    private static $initialized = false;

    /**
     * Initializes the properties.
     */
    public static function init(): void
    {
        if(self::$initialized)
        {
            return;
        }

        /*
        self::registerProperty(
            new BasicDPPropertyInfo(
                "property.generic.num.hits"
            )
        );
        */
        self::$initialized = true;
    }

    /**
     * @param GPPropertyInfo $info
     * @param bool $override - Determines whether to override the property.
     *
     * Registers the property from the information.
     */
    public static function registerProperty(GPPropertyInfo $info, bool $override = false): void
    {
        if(!$override && isset(self::$registeredProperties[$info->getPropertyLocalizedName()]))
        {
            return;
        }

        if(!$info->validate())
        {
            return;
        }

        self::$registeredProperties[$info->getPropertyLocalizedName()] = $info;
    }

    /**
     * @param string $propertyLocalized
     * @param Item $item
     * @param mixed $defaultValue
     * @return IGamePlayerProperty|null
     *
     * Creates the property instance based on the property name.
     */
    public function createPropertyInstance(string $propertyLocalized, Item $item, $defaultValue = null): ?IGamePlayerProperty
    {
        if(!isset(self::$registeredProperties[$propertyLocalized]))
        {
            return null;
        }

        $info = self::$registeredProperties[$propertyLocalized];
        return $info->convertToInstance($item, $defaultValue);
    }

    // ---------------------------------- The Instance of the Game Player Class -----------------------

    /** @var IGamePlayerProperty[] */
    protected $properties = [];
    /** @var PracticePlayer */
    private $player;
    /** @var UUID */
    private $serverID;
    /** @var bool */
    private $online = true;

    public function __construct(PracticePlayer $player)
    {
        $this->player = $player;
        $this->serverID = $player->getServerID();

        $this->initProperties();
    }

    /**
     * Initializes the misc to the player.
     */
    abstract protected function initProperties(): void;

    /**
     * @param $property
     *
     * Adds the property to the game player.
     */
    protected function addProperty($property): void
    {
        if(
            !$property instanceof IGamePlayerProperty
            || isset($this->properties[$property->getLocalized()]))
        {
           return;
        }

        $this->properties[$property->getLocalized()] = $property;
    }

    /**
     * @param string $name
     * @return IGamePlayerProperty|null
     *
     * Gets the property based on its name.
     */
    public function getProperty(string $name): ?IGamePlayerProperty
    {
        if(isset($this->properties[$name]))
        {
            return $this->properties[$name];
        }

        return null;
    }

    /**
     * @return IGamePlayerProperty[]
     *
     * Gets the misc from the player.
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return PracticePlayer
     *
     * Gets the practice player wrapper class.
     */
    public function getPlayer(): PracticePlayer
    {
        return $this->player;
    }

    /**
     * @return UUID
     *
     * Gets the player's server id.
     */
    public function getServerID(): UUID
    {
        return $this->serverID;
    }

    /**
     * @param bool $rawCheck - Determines whether we should check based on boolean
     *        within the class or not.
     * @return bool
     *
     * Determines if a player is online or not,
     * based on a raw check or not.
     */
    public function isOnline(bool $rawCheck = false): bool
    {
        if($rawCheck)
        {
            return $this->player->isOnline();
        }

        return $this->online && $this->player->isOnline();
    }

    /**
     * Sets the player as offline, called on the PlayerQuitEvent.
     */
    public function setOffline(): void
    {
        $this->online = false;
    }

    /**
     * @param $player
     * @return bool
     *
     * Determines if another player is equivalent.
     */
    abstract public function equals($player): bool;
}