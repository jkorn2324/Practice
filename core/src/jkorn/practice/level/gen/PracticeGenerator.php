<?php

declare(strict_types=1);

namespace jkorn\practice\level\gen;


use pocketmine\level\ChunkManager;
use pocketmine\level\generator\Generator;
use pocketmine\utils\Random;

use \stdClass;

abstract class PracticeGenerator extends Generator
{
    /** @var ChunkManager */
    protected $level;
    /** @var Random */
    protected $random;

    /** @var int */
    protected $count = 0;

    /**
     * PracticeGenerator constructor.
     * @param array $settings
     */
    public function __construct(array $settings = []) {}

    /**
     * @param ChunkManager $level
     * @param Random $random
     *
     * Initializes the default practice generator.
     */
    public function init(ChunkManager $level, Random $random): void
    {
        $this->level = $level;
        $this->random = $random;
    }

    /**
     * @param int $chunkX
     * @param int $chunkZ
     *
     * Populates a chunk.
     */
    public function populateChunk(int $chunkX, int $chunkZ): void {}

    /**
     * @return stdClass
     *
     * Extracts the data from the generator, used by the PracticeGeneratorInfo
     * class.
     */
    abstract public static function extractData(): stdClass;

    /**
     * @return string
     *
     * Gets the generator name of the arena, used when data is extracted.
     */
    abstract protected static function getGeneratorName(): string;

    /**
     * @return string
     *
     * Gets the name of the generator.
     */
    public function getName(): string
    {
        return self::getGeneratorName();
    }

    /**
     * @return array
     *
     * Gets the settings of the practice generator.
     */
    public function getSettings(): array
    {
        return [];
    }
}