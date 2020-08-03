<?php

declare(strict_types=1);

namespace jkorn\practice\kits\data;


use jkorn\practice\misc\ISaved;
use jkorn\practice\PracticeUtil;
use pocketmine\entity\EffectInstance;
use pocketmine\Player;

class KitEffectsData implements ISaved
{

    const EFFECTS_HEADER = "effects";

    /** @var EffectInstance[] */
    private $effects;

    /**
     * KitEffectsData constructor.
     * @param array|EffectInstance[] $effects
     */
    public function __construct($effects = [])
    {
        $this->effects = $effects;
    }

    /**
     * @param EffectInstance $instance
     *
     * Adds an effect to the effect list.
     */
    public function addEffect(EffectInstance $instance): void
    {
        $this->effects[$instance->getType()->getId()] = $instance;
    }

    /**
     * @param EffectInstance $instance
     *
     * Removes an effect from the effect list.
     */
    public function removeEffect(EffectInstance $instance): void
    {
        if(isset($this->effects[$instance->getType()->getId()]))
        {
            unset($this->effects[$instance->getType()->getId()]);
        }
    }

    /**
     * @return array|EffectInstance[]
     *
     * Gets the effects of the kit.
     */
    public function getEffects()
    {
        return $this->effects;
    }

    /**
     * @param Player $player
     *
     * Sends the effects to the player.
     */
    public function sendTo(Player $player): void
    {
        foreach($this->effects as $effect)
        {
            $player->addEffect($effect);
        }
    }

    /**
     * @return array
     *
     * Exports the effects data to an array.
     */
    public function export(): array
    {
        $output = [];
        foreach($this->effects as $effect)
        {
            $output[] = PracticeUtil::effectToArr($effect);
        }
        return $output;
    }

    /**
     * @param $data
     * @return KitEffectsData
     *
     * Decodes the effects data from an array to an object.
     */
    public static function decode($data): KitEffectsData
    {
        if(is_array($data) && count($data) > 0)
        {
            $effects = [];
            foreach($data as $effectData)
            {
                $effect = PracticeUtil::arrToEffect($effectData);
                if($effect !== null)
                {
                    $effects[$effect->getType()->getId()] = $effect;
                }
            }
            return new KitEffectsData($effects);
        }

        return new KitEffectsData();
    }
}