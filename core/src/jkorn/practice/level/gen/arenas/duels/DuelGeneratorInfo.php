<?php

declare(strict_types=1);

namespace jkorn\practice\level\gen\arenas\duels;


use jkorn\practice\level\gen\PracticeGeneratorInfo;

class DuelGeneratorInfo extends PracticeGeneratorInfo
{

    const TYPE_ANY = "any";
    const TYPE_1VS1 = "1vs1";
    const TYPE_TEAM = "team";

    /** @var string */
    protected $type;

    /**
     * DuelGeneratorInfo constructor.
     * @param string $type
     * @param $class
     */
    public function __construct($class, string $type = self::TYPE_ANY)
    {
        parent::__construct("", $class);
        $this->type = $type;
    }

    /**
     * @return string
     *
     * Gets the type of duel this generator is for.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Extracts the data from the generator class.
     */
    public function extract(): void
    {
        try {

            $reflectionClass = new \ReflectionClass($this->class);
            $method = $reflectionClass->getMethod("extractData");
            $extraData = $method->invokeArgs(null, []);
            if($extraData instanceof \stdClass)
            {
                $this->extraData = $extraData;

                // Saves the generator name to the info.
                if(isset($this->extraData->generatorName))
                {
                    $this->generatorName = $this->extraData->generatorName;
                }
            }

        } catch (\Exception $e) {
            // TODO: Print stack.
            return;
        }
    }
}