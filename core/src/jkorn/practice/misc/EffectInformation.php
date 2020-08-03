<?php

declare(strict_types=1);

namespace jkorn\practice\misc;


use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;

class EffectInformation
{

    /** @var EffectInformation[] */
    private static $information = [];

    /** @var bool - Determines if the effect information is initialized. */
    private static $initialize = false;

    /**
     * Initializes the effects information.
     */
    private static function initialize(): void
    {
        // Registers the effects.
        self::register(new EffectInformation(Effect::SPEED,
            "Speed", "textures/ui/speed_effect.png"));
        self::register(new EffectInformation(Effect::SLOWNESS,
            "Slowness", "textures/ui/slowness_effect.png"));
        self::register(new EffectInformation(Effect::HASTE,
            "Haste", "textures/ui/haste_effect.png"));
        self::register(new EffectInformation(Effect::MINING_FATIGUE,
            "Mining Fatigue", "textures/ui/mining_fatigue_effect.png"));
        self::register(new EffectInformation(Effect::STRENGTH,
            "Strength", "textures/ui/strength_effect.png"));
        self::register(new EffectInformation(Effect::INSTANT_HEALTH,
            "Instant Health", "textures/items/potion_bottle_splash_heal.png"));
        self::register(new EffectInformation(Effect::INSTANT_DAMAGE,
            "Instant Damage", "textures/items/potion_bottle_splash_harm.png"));
        self::register(new EffectInformation(Effect::JUMP_BOOST,
            "Jump Boost", "textures/ui/jump_boost_effect.png"));
        self::register(new EffectInformation(Effect::NAUSEA,
            "Nausea", "textures/ui/nausea_effect.png"));
        self::register(new EffectInformation(Effect::REGENERATION,
            "Regeneration", "textures/ui/regeneration_effect.png"));
        self::register(new EffectInformation(Effect::RESISTANCE,
            "Resistance", "textures/ui/resistance_effect.png"));
        self::register(new EffectInformation(Effect::FIRE_RESISTANCE,
            "Fire Resistance", "textures/ui/fire_resistance_effect.png"));
        self::register(new EffectInformation(Effect::WATER_BREATHING,
            "Water Breathing", "textures/ui/water_breathing_effect.png"));
        self::register(new EffectInformation(Effect::INVISIBILITY,
            "Invisibility", "textures/ui/invisibility_effect.png"));
        self::register(new EffectInformation(Effect::BLINDNESS,
            "Blindness", "textures/ui/blindness_effect.png"));
        self::register(new EffectInformation(Effect::NIGHT_VISION,
            "Night Vision", "textures/ui/night_vision_effect.png"));
        self::register(new EffectInformation(Effect::HUNGER,
            "Hunger", "textures/ui/hunger_effect_full.png"));
        self::register(new EffectInformation(Effect::WEAKNESS,
            "Weakness", "textures/ui/weakness_effect.png"));
        self::register(new EffectInformation(Effect::POISON,
            "Poison", "textures/ui/poison_effect.png"));
        self::register(new EffectInformation(Effect::WITHER,
            "Wither", "textures/ui/wither_effect.png"));
        self::register(new EffectInformation(Effect::HEALTH_BOOST,
            "Health Boost", "textures/ui/health_boost_effect.png"));
        self::register(new EffectInformation(Effect::ABSORPTION,
            "Absorption", "textures/ui/absorption_effect.png"));
        self::register(new EffectInformation(Effect::SATURATION,
            "Saturation", "textures/ui/hunger_effect.png"));
        self::register(new EffectInformation(Effect::LEVITATION,
            "Levitation", "textures/ui/levitation_effect.png"));
        self::register(new EffectInformation(Effect::FATAL_POISON,
            "Fatal Poison", "textures/ui/poison_effect.png"));
        self::register(new EffectInformation(Effect::CONDUIT_POWER,
            "Conduit Power", "textures/ui/conduit_power_effect.png"));

        self::$initialize = true;
    }

    /**
     * @param EffectInformation $information
     *
     * Registers the effect information to the list.
     */
    private static function register(EffectInformation $information): void
    {
        self::$information[$information->getEffect()->getId()] = $information;
    }

    /**
     * @return EffectInformation[]
     *
     * Gets all of the effect information.
     */
    public static function getAll()
    {
        if(!self::$initialize)
        {
            self::initialize();
        }

        return self::$information;
    }

    /**
     * @param $effect
     * @return EffectInformation|null
     *
     * Gets the effect information from the effect.
     */
    public static function getInformation($effect): ?EffectInformation
    {
        if(!self::$initialize)
        {
            self::initialize();
        }

        if($effect instanceof EffectInstance)
        {
            return self::getInformation($effect->getType());
        }
        elseif ($effect instanceof Effect)
        {
            if(isset(self::$information[$effect->getId()]))
            {
                return self::$information[$effect->getId()];
            }
        }
        elseif (is_int($effect))
        {
            if(isset(self::$information[$effect]))
            {
                return self::$information[$effect];
            }
        }

        return null;
    }

    // ---------------------------- The Effect Information Instance ------------------

    /** @var string */
    private $texture = "";

    /** @var string */
    private $name = "";

    /** @var int */
    private $effectID;

    /**
     * EffectInformation constructor.
     *
     * @param int $effectID
     * @param string $name
     * @param string $texture
     *
     * The constructor used so generate the effect information.
     */
    private function __construct(int $effectID, string $name, string $texture)
    {
        $this->effectID = $effectID;
        $this->name = $name;
        $this->texture = $texture;
    }

    /**
     * @return string
     *
     * Gets the default name of the effect in English.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Effect|null
     *
     * Gets the literal effect.
     */
    public function getEffect(): ?Effect
    {
        return Effect::getEffect($this->effectID);
    }

    /**
     * @return EffectInstance|null
     *
     * Creates a new effect instance.
     */
    public function createInstance(): ?EffectInstance
    {
        $effect = $this->getEffect();
        if($effect === null)
        {
            return null;
        }

        return new EffectInstance($effect);
    }

    /**
     * @return string
     *
     * Gets the form texture of the effect information.
     */
    public function getFormTexture(): string
    {
        return $this->texture;
    }
}