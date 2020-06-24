<?php

declare(strict_types=1);

namespace practice\kits;


use pocketmine\entity\EffectInstance;
use pocketmine\item\Item;
use pocketmine\Player;
use practice\player\PracticePlayer;
use practice\misc\ISaved;
use practice\PracticeUtil;

class Kit implements ISaved
{
    /** @var Item[] */
    private $items;
    /** @var Item[] */
    private $armor;

    /** @var string */
    private $texture;

    /** @var EffectInstance[] */
    private $effects;
    /** @var string */
    private $name;

    /** @var KitCombatData */
    private $combatData;

    /** @var bool */
    private $build;

    public function __construct(string $name, array $items, array $armor, array $effects, KitCombatData $data, string $texture = "", bool $canBuild = false)
    {
        $this->name = $name;
        $this->texture = $texture;
        $this->items = $items;
        $this->armor = $armor;
        $this->effects = $effects;
        $this->combatData = $data;
        $this->build = $canBuild;
    }

    /**
     * @param Player $player
     * @param bool $sendMessage - Determines whether or not to send the message to the player.
     *
     * Sends the kit to the player.
     */
    public function sendTo(Player $player, bool $sendMessage = true): void
    {
        if($player instanceof PracticePlayer)
        {
            $player->setEquipped($this);
        }

        $player->clearInventory();
        $player->removeAllEffects();

        // Sends the items to the player.
        foreach($this->items as $slot => $item)
        {
            $player->getInventory()->setItem($slot, $item);
        }

        // Sends the armor to the player.
        foreach($this->armor as $slot => $item)
        {
            $player->getArmorInventory()->setItem($slot, $item);
        }

        // Sends the effects to the player.
        foreach($this->effects as $effect)
        {
            $player->addEffect($effect);
        }

        if($sendMessage)
        {
            // TODO: Send message to the player.
        }
    }


    /**
     * @return string
     *
     * Gets the name of the kit.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return KitCombatData
     *
     * Gets the kit combat data.
     */
    public function getCombatData(): KitCombatData
    {
        return $this->combatData;
    }

    /**
     * @return bool
     *
     * Determines if the player can build while using that kit.
     */
    public function canBuild(): bool
    {
        return $this->build;
    }

    /**
     * @return array
     *
     * Exports the kit data so that it's saved.
     */
    public function export(): array
    {
        $items = []; $armor = []; $effects = [];

        foreach($this->items as $slot => $item)
        {
            if($item->getId() === Item::get(0))
            {
                continue;
            }
            $items[$slot] = PracticeUtil::itemToArr($item);
        }

        foreach($this->armor as $slot => $item)
        {
            $armor[PracticeUtil::convertArmorIndex($slot)] = PracticeUtil::itemToArr($item);
        }

        foreach($this->effects as $effect)
        {
            $effects[] = PracticeUtil::effectToArr($effect);
        }

        return [
            "items" => $items,
            "armor" => $armor,
            "effects" => $effects,
            "texture" => $this->texture,
            $this->combatData->getHeader() => $this->combatData->export(),
            "build" => $this->build
        ];
    }

    /**
     * @param $kit - The kit variable.
     * @return bool
     *
     * Determines if a kit equals another.
     */
    public function equals($kit): bool
    {
        if($kit instanceof Kit)
        {
            return $kit->getName() === $this->name;
        }

        return false;
    }

    /**
     * @param string $name
     * @param $data
     * @return Kit|null
     *
     * Decodes the kit from a data set.
     */
    public static function decode(string $name, $data): ?Kit
    {
        if(!isset($data["items"], $data["armor"], $data["effects"], $data[KitCombatData::KIT_HEADER], $data["texture"]))
        {
            return null;
        }

        $build = isset($data["build"]) ? (bool)$data["build"] : false;

        $dataItems = $data["items"]; $outputItems = [];
        $dataArmor = $data["armor"]; $outputArmor = [];
        $dataEffects = $data["effects"]; $outputEffects = [];

        foreach($dataItems as $slot => $item)
        {
            $exportedItem = PracticeUtil::arrToItem($item);
            if($exportedItem !== null)
            {
                $outputItems[$slot] = $exportedItem;
            }
        }

        foreach($dataArmor as $slot => $item)
        {
            $exportedItem = PracticeUtil::arrToItem($item);
            if($exportedItem !== null)
            {
                $outputArmor[PracticeUtil::convertArmorIndex($slot)] = $exportedItem;
            }
        }

        foreach($dataEffects as $effect)
        {
            $exportedEffect = PracticeUtil::arrToEffect($effect);
            if($exportedEffect !== null)
            {
                $outputEffects[] = $exportedEffect;
            }
        }

        return new Kit(
            $name,
            $outputItems,
            $outputArmor,
            $outputEffects,
            KitCombatData::decode($data[KitCombatData::KIT_HEADER]),
            $data["texture"],
            $build
        );
    }
}