<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-19
 * Time: 08:13
 */

declare(strict_types=1);

namespace practice\ranks;

class Rank
{

    private $localizedName;

    private $name;

    public function __construct(string $local, string $name)
    {
        $this->localizedName = $local;
        $this->name = $name;
    }

    public function getLocalizedName() : string {
        return $this->localizedName;
    }

    public function getName() : string {
        return $this->name;
    }

    public function equals($object) {
        $result = false;
        if($object instanceof Rank)
            $result = $object->getLocalizedName() === $this->localizedName;
        return $result;
    }
}