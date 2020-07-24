<?php

declare(strict_types=1);

namespace jkorn\practice\level\gen\arenas;


use jkorn\practice\level\gen\PracticeGenerator;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\level\ChunkManager;
use pocketmine\level\format\Chunk;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;


abstract class ArenaGenerator extends PracticeGenerator
{

    /** @var int - Gets the arena length in chunks (16 Blocks)  */
    protected static $arenaChunkLength = 3;
    /** @var int - Gets the arena width in chunks (16 Blocks) */
    protected static $arenaChunkWidth = 3;
    /** @var int - Gets the arena height. */
    protected static $arenaHeight = 10;
    /** @var int - Gets ths start y of the arena. */
    protected static $arenaStartY = 100;

    /** @var Block[] */
    protected $blocks = [];

    /**
     * @param ChunkManager $level
     * @param Random $random
     *
     * Initializes the ArenaGenerator.
     */
    public function init(ChunkManager $level, Random $random): void
    {
        parent::init($level, $random);
        $this->initBlocks();
    }

    /**
     * Initializes the blocks.
     */
    abstract protected function initBlocks(): void;


    /**
     * @param int $chunkX
     * @param int $chunkZ
     *
     * Generates the chunks.
     */
    public function generateChunk(int $chunkX, int $chunkZ): void
    {
        if($this->level instanceof ChunkManager)
        {
            $chunk = $this->level->getChunk($chunkX, $chunkZ);
            $chunkXCoord = $chunkX; $chunkZCoord = $chunkZ;
            if($chunkXCoord >= 0 && $chunkXCoord < self::$arenaChunkLength && $chunkZCoord >= 0 && $chunkZCoord < self::$arenaChunkWidth)
            {
                for($x = 0; $x < 16; $x++)
                {
                    for($z = 0; $z < 16; $z++)
                    {
                        if($this->isBarrier($chunkXCoord, $chunkZCoord, $x, $z))
                        {
                            $this->setBarrier($chunk, $x, $z);
                        } else {
                            $this->setFloor($chunk, $chunkXCoord, $chunkZCoord, $x, $z);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param int $chunkXCoord
     * @param int $chunkZCoord
     * @param int $x
     * @param int $z
     * @return bool
     *
     * Determines if the given coords are a barrier.
     */
    private function isBarrier(int $chunkXCoord, int $chunkZCoord, int $x, int $z): bool {

        $xCheck = false;
        if($chunkXCoord == 0) {
            $xCheck = $x == 0;
        } elseif ($chunkXCoord == (self::$arenaChunkLength - 1)) {
            $xCheck = $x == 15;
        }

        $zCheck = false;
        if($chunkZCoord == 0) {
            $zCheck = $z == 0;
        } elseif ($chunkZCoord == (self::$arenaChunkWidth - 1)) {
            $zCheck = $z == 15;
        }

        return $xCheck || $zCheck;
    }

    /**
     * @param Chunk $chunk
     * @param int $x
     * @param int $z
     *
     * Sets the barrier of the generator.
     */
    protected function setBarrier(Chunk $chunk, int $x, int $z): void
    {
        $spawnHeight = $this->getSpawn()->y; $yCeiling = $this->getCeilingY();
        for($y = $spawnHeight - 1; $y < $yCeiling; $y++)
        {
            $chunk->setBlock($x, $y, $z, BlockIds::INVISIBLE_BEDROCK);
        }
    }

    /**
     * @return Vector3
     *
     * Gets the center position.
     */
    abstract protected static function getCenter(): Vector3;

    /**
     * @param Chunk $chunk
     * @param int $chunkXCoord
     * @param int $chunkZCoord
     * @param int $x
     * @param int $z
     *
     * Sets the floor of the generator at a given x, z in a chunkCoord area.
     */
    abstract protected function setFloor(Chunk $chunk, int $chunkXCoord, int $chunkZCoord, int $x, int $z): void;

    /**
     * @return float
     *
     * Gets the y coordinate of the ceiling.
     */
    protected function getCeilingY(): float
    {
        return $this->getSpawn()->y + self::$arenaHeight;
    }

    /**
     * @return Vector3
     *
     * Gets the spawn position of the arena, mainly used
     * so that we get the y start position.
     */
    public function getSpawn(): Vector3
    {
        return new Vector3(0, self::$arenaStartY, 0);
    }
}