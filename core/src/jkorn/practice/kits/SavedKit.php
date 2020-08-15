<?php

declare(strict_types=1);

namespace jkorn\practice\kits;

use jkorn\practice\forms\types\properties\ButtonTexture;
use jkorn\practice\kits\data\KitEffectsData;
use jkorn\practice\kits\data\KitCombatData;
use jkorn\practice\messages\IPracticeMessages;
use jkorn\practice\messages\managers\PracticeMessageManager;
use jkorn\practice\PracticeCore;
use pocketmine\item\Item;
use pocketmine\Player;
use jkorn\practice\player\PracticePlayer;
use jkorn\practice\misc\ISaved;
use jkorn\practice\PracticeUtil;

class SavedKit implements ISaved, IKit
{
    /** @var Item[] */
    private $items;
    /** @var Item[] */
    private $armor;

    /** @var ButtonTexture|null */
    private $buttonTexture;

    /** @var string */
    private $name;

    /** @var KitCombatData */
    private $combatData;
    /** @var KitEffectsData */
    private $effectsData;

    /** @var bool */
    private $build;

    public function __construct(string $name, array $items, array $armor, KitEffectsData $effectsData, KitCombatData $combatData, ?ButtonTexture $texture = null, bool $canBuild = false)
    {
        $this->name = $name;
        $this->buttonTexture = $texture;
        $this->items = $items;
        $this->armor = $armor;
        $this->effectsData = $effectsData;
        $this->combatData = $combatData;
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
        $this->effectsData->sendTo($player);

        if($sendMessage)
        {
            // TODO: Add prefix to default text.
            $text = "You have equipped the " . $this->getName() . " kit!";
            $messageManager = PracticeCore::getBaseMessageManager()->getMessageManager(PracticeMessageManager::NAME);
            if($messageManager !== null)
            {
                $textMessage = $messageManager->getMessage(IPracticeMessages::PLAYER_KIT_EQUIP_MESSAGE);
                if($textMessage !== null)
                {
                    $text = $textMessage->getText($player, $this);
                }
            }
            $player->sendMessage($text);
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
     * @return KitEffectsData
     *
     * Gets the kit effects data.
     */
    public function getEffectsData(): KitEffectsData
    {
        // TODO: Implement getEffectsData() method.
        return $this->effectsData;
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
        $items = []; $armor = [];

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

        return [
            "items" => $items,
            "armor" => $armor,
            KitEffectsData::EFFECTS_HEADER => $this->effectsData->export(),
            "texture" => $this->buttonTexture !== null ? $this->buttonTexture->export() : null,
            KitCombatData::KIT_HEADER => $this->combatData->export(),
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
        if($kit instanceof SavedKit)
        {
            return $kit->getName() === $this->name;
        }

        return false;
    }

    /**
     * @return ButtonTexture|null
     *
     * Gets the button texture information.
     */
    public function getFormButtonTexture(): ?ButtonTexture
    {
        return $this->buttonTexture;
    }


    /**
     * @param ButtonTexture $texture
     *
     * Sets the form texture of the kit.
     */
    public function setFormButtonTexture(ButtonTexture $texture): void
    {
        $this->buttonTexture = $texture;
    }

    /**
     * @param string $name
     * @param $data
     * @return SavedKit|null
     *
     * Decodes the kit from a data set.
     */
    public static function decode(string $name, $data)
    {
        if(!isset($data["items"], $data["armor"], $data[KitEffectsData::EFFECTS_HEADER], $data[KitCombatData::KIT_HEADER]))
        {
            return null;
        }

        $build = isset($data["build"]) ? (bool)$data["build"] : false;

        $dataItems = $data["items"]; $outputItems = [];
        $dataArmor = $data["armor"]; $outputArmor = [];

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

        $texture = null;
        if(isset($data["texture"]))
        {
            $texture = ButtonTexture::decode($data["texture"]);
        }

        return new SavedKit(
            $name,
            $outputItems,
            $outputArmor,
            KitEffectsData::decode($data[KitEffectsData::EFFECTS_HEADER]),
            KitCombatData::decode($data[KitCombatData::KIT_HEADER]),
            $texture,
            $build
        );
    }
}