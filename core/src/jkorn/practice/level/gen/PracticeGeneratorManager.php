<?php

declare(strict_types=1);

namespace jkorn\practice\level\gen;

use pocketmine\level\generator\GeneratorManager;

class PracticeGeneratorManager
{
    /** @var PracticeGenerator[] */
    private static $generators = [];

    /**
     * Initializes the default generators.
     */
    public static function init(): void {}

    /**
     * @param PracticeGeneratorInfo $info -> The information of the generator.
     *
     * Registers the generator to the default generator list.
     */
    public static function registerGenerator(PracticeGeneratorInfo $info): void
    {
        if(!is_subclass_of($clazz = $info->getClass(), PracticeGenerator::class))
        {
            return;
        }
        $info->extract();
        self::$generators[$info->getName()] = $info;
        GeneratorManager::addGenerator($clazz, $info->getName(), true);
    }

    /**
     * @param string $name
     * @return PracticeGenerator|null
     *
     * Gets the generator information.
     */
    public static function getGeneratorInfo(string $name)
    {
        if(isset(self::$generators[$name]))
        {
            return self::$generators[$name];
        }
        return null;
    }

    /**
     * @param callable|null $function - Determines whether or not we want
     *        to filter them. Accepts a PracticeGeneratorInfo parameter and should
     *        return a boolean:
     *        EX: getGenerators(function(PracticeGeneratorInfo $info) { return true; });
     * @return array|PracticeGenerator[]
     *
     * Gets a list of generator information.
     */
    public static function getGenerators(?callable $function = null)
    {
        if($function !== null)
        {
            return array_filter(self::$generators, $function);
        }
        return self::$generators;
    }

    /**
     * @param callable|null $function
     * @return PracticeGeneratorInfo|null
     *
     * Gets the random generator from the generator list.
     */
    public static function randomGenerator(?callable $function = null): ?PracticeGeneratorInfo
    {
        $generators = self::getGenerators($function);
        if(count($generators) <= 0)
        {
            return null;
        }
        $keys = array_keys($generators);
        return $generators[$keys[mt_rand(0, count($keys) - 1)]];
    }
}