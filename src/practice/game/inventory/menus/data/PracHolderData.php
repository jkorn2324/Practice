<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-03
 * Time: 19:51
 */

namespace practice\game\inventory\menus\data;


use pocketmine\math\Vector3;

class PracHolderData
{
    private $position;

    private $customName;

    public function __construct(Vector3 $position, string $name){
        $this->position = $position;
        $this->customName = $name;
    }

    public function getPos() : Vector3 {
        return $this->position;
    }

    public function getCustomName() : string {
        return $this->customName;
    }

}