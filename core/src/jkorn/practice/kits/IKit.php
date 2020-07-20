<?php

declare(strict_types=1);

namespace jkorn\practice\kits;


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
     * @return string
     *
     * Gets the texture of the kit.
     */
    public function getTexture(): string;
}