<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-19
 * Time: 17:05
 */

declare(strict_types=1);

namespace practice\game\effects;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\Player;

class PracticeEffect
{

    private $duration;

    private $effect;

    private $amplifier;

    public function __construct(Effect $effect, int $duration, int $amp) {
        $this->effect = $effect;
        $this->duration = $duration;
        $this->amplifier = $amp;
    }

    public function getEffect() : Effect {
        return $this->effect;
    }

    public function getDuration() : int {
        return $this->duration;
    }

    public function getAmplifier() : int {
        return $this->amplifier;
    }

    public function applyTo($player) : void {
        if($player instanceof Player){
            $effect = new EffectInstance($this->effect, $this->duration * 20, $this->amplifier);
            $player->addEffect($effect);
        }
    }

    public function toString() : string {
        $id = $this->effect->getId();
        $str = $id . ":" . $this->amplifier . ":" . $this->duration;
        return $str;
    }

    public static function getEffectFrom(string $line) : PracticeEffect {
        $split = explode(":", $line);
        $id = intval($split[0]);
        $amp = intval($split[1]);
        $duration = intval($split[2]);
        $effect = Effect::getEffect($id);
        return new PracticeEffect($effect, $duration, $amp);
    }
}