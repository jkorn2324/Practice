<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-13
 * Time: 19:01
 */

declare(strict_types=1);

namespace practice\commands\parameters;


class BaseParameter implements Parameter
{

    private $permission;

    private $name;

    private $description;

    /**
     * BaseParameter constructor.
     * @param string $n
     * @param string $basePermission
     * @param string $desc
     * @param bool $hasPerm
     */
    public function __construct(string $name, string $basePermission, string $desc, bool $hasPerm = true)
    {
        $this->name = $name;
        $this->description = $desc;
        if($hasPerm and $basePermission !== Parameter::NO_PERMISSION){
            $this->permission = "$basePermission." . $this->getName();
        } else {
            $this->permission = Parameter::NO_PERMISSION;
        }
    }

    /**
     * @return bool
     */
    public function hasPermission() : bool
    {
        return $this->permission != null and $this->permission !== Parameter::NO_PERMISSION;
    }

    /**
     * @return |null
     */
    public function getPermission() : string {
        return $this->permission;
    }

    /**
     * @return string
     */
    public function getDescription() : string {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

}