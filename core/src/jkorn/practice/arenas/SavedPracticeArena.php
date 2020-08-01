<?php

declare(strict_types=1);

namespace jkorn\practice\arenas;


use jkorn\practice\misc\ISaved;

/**
 * Class SavedPracticeArena
 * @package jkorn\practice\arenas
 *
 * Used as a base class for arenas that are meant to be saved.
 */
abstract class SavedPracticeArena extends PracticeArena implements ISaved
{

    /**
     * @param string $name
     * @param array $data
     * @return mixed
     *
     * Decodes the arena based on the name and data.
     */
    abstract public static function decode(string $name, array $data);
}