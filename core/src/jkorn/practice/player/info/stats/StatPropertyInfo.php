<?php

declare(strict_types=1);

namespace jkorn\practice\player\info\stats;


class StatPropertyInfo
{

    /** @var string */
    private $name;

    /** @var string */
    private $class;

    /** @var mixed */
    private $defaultValue;
    /** @var bool */
    private $saved;

    public function __construct(string $name, $class, bool $save, $defaultValue = null)
    {
        $this->name = $name;
        $this->class = $class;
        $this->defaultValue = $defaultValue;
        $this->saved = $save;
    }

    /**
     * @return string
     *
     * Gets the name of the stat property.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return IStatProperty
     *
     * Converts the stat
     */
    public function convertToInstance(): IStatProperty
    {
        $class = $this->class;
        if($this->defaultValue === null)
        {
            return new $class($this->name, $this->saved);
        }
        else
        {
            return new $class($this->name, $this->saved, $this->defaultValue);
        }
    }
}