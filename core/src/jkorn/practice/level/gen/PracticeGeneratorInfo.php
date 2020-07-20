<?php

declare(strict_types=1);

namespace jkorn\practice\level\gen;


use stdClass;

abstract class PracticeGeneratorInfo
{

    /** @var string */
    protected $class;

    /** @var string */
    protected $generatorName;

    /** @var stdClass */
    protected $extraData;

    public function __construct(string $generatorName, $class)
    {
        $this->class = $class;
        $this->generatorName = $generatorName;
        $this->extraData = new \stdClass();
    }

    /**
     * @return string
     *
     * Gets the generator name.
     */
    public function getName(): string
    {
        return $this->generatorName;
    }

    /**
     * @return string
     *
     * Gets the class of the generator information.
     */
    public function getClass(): string
    {
        return $this->class;
    }


    /**
     * @return stdClass
     *
     * Gets the extra data of the generator, extracted from the generator class.
     */
    public function getExtraData(): stdClass
    {
        return $this->extraData;
    }


    /**
     * Extracts the data from the generator class.
     */
    abstract public function extract(): void;
}