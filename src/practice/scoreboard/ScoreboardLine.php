<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-10
 * Time: 16:46
 */

namespace practice\scoreboard;


abstract class ScoreboardLine
{

    abstract public function getText() : string;

    abstract public function setHidden(bool $hidden = true);

    abstract public function isHidden() : bool;

    abstract public function getId() : int;

}