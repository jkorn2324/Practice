<?php

declare(strict_types=1);

namespace jkorn\bd\duels\types;


use jkorn\practice\games\misc\gametypes\info\IGameInfo;

class BasicDuelGameInfo implements IGameInfo
{

    /** @var string */
    private $name;
    /** @var string */
    private $texture;
    /** @var int */
    private $numPlayers;

    public function __construct(int $numPlayers, string $name, string $texture)
    {
        $this->name = $name;
        $this->texture = $texture;
        $this->numPlayers = $numPlayers;
    }

    /**
     * @return string
     *
     * Gets the texture of the game type.
     */
    public function getTexture(): string
    {
        return $this->texture;
    }

    /**
     * @return string
     *
     * Gets the localized name of the game type.
     */
    public function getLocalizedName(): string
    {
        return strtolower($this->name);
    }

    /**
     * @return string
     *
     * Gets the display name of the game type.
     */
    public function getDisplayName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     *
     * Gets the total number of players.
     */
    public function getNumberOfPlayers(): int
    {
        return $this->numPlayers;
    }
    /**
     * @param $object
     * @return bool
     *
     * Determines if a game type is equivalent.
     */
    public function equals($object): bool
    {
        if($object instanceof BasicDuelGameInfo)
        {
            return $object->getLocalizedName() === $this->getLocalizedName();
        }

        return false;
    }
}