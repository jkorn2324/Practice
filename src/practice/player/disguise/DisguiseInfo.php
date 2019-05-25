<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-11
 * Time: 15:51
 */

declare(strict_types=1);

namespace practice\player\disguise;


use pocketmine\entity\Skin;
use pocketmine\Server;

class DisguiseInfo
{

    private $name;

    /* @var \pocketmine\entity\Skin|null */
    private $skin;

    public function __construct(string $name, Skin $skin = null) {
        if(!is_null($skin)) $this->skin = $skin;
        else $this->skin = $this->randomSkin();
        $this->name = $name;
    }

    public function getName() : string {
        return $this->name;
    }

    private function randomSkin() : Skin {
        $result = null;
        $size = count(Server::getInstance()->getOnlinePlayers());
        if($size > 2) {
            foreach(Server::getInstance()->getOnlinePlayers() as $player) {
                $skin = $player->getSkin();
            }
        }
    }

    public function getSkin() {
        return $this->skin;
    }
}