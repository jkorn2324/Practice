<?php

declare(strict_types=1);

namespace practice\arenas\types;


use pocketmine\level\Level;
use practice\arenas\PracticeArena;
use practice\kits\Kit;
use practice\misc\PositionArea;

class FFAArena extends PracticeArena
{

    /** @var Kit|null */
    private $kit;

    public function __construct(string $name, Level $level, PositionArea $area, ?Kit $kit = null)
    {
        parent::__construct($name, $level, $area);
        $this->kit = $kit;
    }

    /**
     * @param Kit|null $kit
     *
     * Sets the kit of the ffa arena.
     */
    public function setKit(?Kit $kit): void
    {
        $this->kit = $kit;
    }

    /**
     * @return Kit|null
     *
     * Gets the kit.
     */
    public function getKit(): ?Kit
    {
        return $this->kit;
    }

    /**
     * @return array
     *
     * Exports the ffa arena to be stored.
     */
    public function export(): array
    {
        // TODO: Implement export() method.
        return [
            "kit" => $this->kit instanceof Kit ? $this->kit->getName() : null,
            $this->positionArea->getHeader() => $this->positionArea->export()
        ];
    }
}