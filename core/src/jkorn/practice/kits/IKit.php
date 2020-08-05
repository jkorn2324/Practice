<?php

declare(strict_types=1);

namespace jkorn\practice\kits;


use jkorn\practice\forms\types\properties\ButtonTexture;
use jkorn\practice\kits\data\KitEffectsData;
use jkorn\practice\kits\data\KitCombatData;
use pocketmine\Player;

interface IKit
{

    /**
     * @param Player $player
     * @param bool $sendMessage
     *
     * Sends the kit to another player.
     */
    public function sendTo(Player $player, bool $sendMessage = true): void;

    /**
     * @return KitCombatData
     *
     * Gets the kit combat data.
     */
    public function getCombatData(): KitCombatData;

    /**
     * @return KitEffectsData
     *
     * Gets the kit effects data.
     */
    public function getEffectsData(): KitEffectsData;

    /**
     * @param $kit
     * @return bool
     *
     * Determines if one kit is equivalent.
     */
    public function equals($kit): bool;

    /**
     * @return string
     *
     * Gets the name of the kit.
     */
    public function getName(): string;

    /**
     * @return ButtonTexture|null
     *
     * Gets the button texture for the kit.
     */
    public function getFormButtonTexture(): ?ButtonTexture;
}