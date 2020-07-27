<?php

declare(strict_types=1);

namespace jkorn\bd\gen\types;


use jkorn\practice\level\gen\arenas\ArenaGenerator;
use jkorn\practice\misc\ColorIDs;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\level\format\Chunk;
use pocketmine\math\Vector3;
use stdClass;

class RedDefault extends ArenaGenerator implements ColorIDs
{

    // Overriden to provide support for team duels.
    protected static $arenaChunkLength = 3;
    protected static $arenaChunkWidth = 3;


    /**
     * Initializes the blocks.
     */
    protected function initBlocks(): void
    {
        $this->blocks = [
            Block::get(Block::CONCRETE, self::BLOCK_COLOR_RED),
            Block::get(Block::CONCRETE, self::BLOCK_COLOR_RED),
            Block::get(Block::WOOL, self::BLOCK_COLOR_RED),
            Block::get(Block::WOOL, self::BLOCK_COLOR_RED),
            Block::get(Block::STAINED_GLASS, self::BLOCK_COLOR_RED)
        ];
    }

    /**
     * @param Chunk $chunk
     * @param int $chunkXCoord
     * @param int $chunkZCoord
     * @param int $x
     * @param int $z
     *
     * Sets the floor of the generator at a given x, z in a chunkCoord area.
     */
    protected function setFloor(Chunk $chunk, int $chunkXCoord, int $chunkZCoord, int $x, int $z): void
    {
        $rand = mt_rand(0, count($this->blocks) - 1);

        /** @var Block $block */
        $block = $this->blocks[$rand];
        $chunk->setBlock($x, 99, $z, $block->getId(), $block->getDamage());

        $underneath = Block::get(BlockIds::BEDROCK);
        if($block->getId() === Block::STAINED_GLASS) {
            $underneath = Block::get(BlockIds::CONCRETE, self::BLOCK_COLOR_RED);
        }

        $chunk->setBlock($x, 98, $z, $underneath->getId(), $underneath->getDamage());
        $chunk->setBlock($x, 97, $z, BlockIds::BEDROCK);
        $chunk->setBlock($x, intval($this->getCeilingY()), $z, BlockIds::INVISIBLE_BEDROCK);
    }

    /**
     * @return stdClass
     *
     * Extracts the data from the generator, used by the PracticeGeneratorInfo
     * class.
     */
    public static function extractData(): stdClass
    {
        $output = new stdClass();
        $output->arenaSizeX = self::$arenaChunkLength * 16;
        $output->arenaSizeZ = self::$arenaChunkWidth * 16;
        $output->arenaHeight = self::$arenaHeight;
        $output->generatorName = self::getGeneratorName();
        $output->center = self::getCenter();
        return $output;
    }

    /**
     * @return Vector3
     *
     * Gets the center of the arena.
     */
    protected static function getCenter(): Vector3
    {
        $centerX = (self::$arenaChunkWidth * 16) / 2;
        $centerZ = (self::$arenaChunkLength * 16) / 2;
        return new Vector3($centerX, self::$arenaStartY, $centerZ);
    }

    /**
     * @return string
     *
     * Gets the generator name of the arena, used when data is extracted.
     */
    protected static function getGeneratorName(): string
    {
        return "basic.duels.red.default";
    }
}