<?php

declare(strict_types=1);

namespace practice\player\info;


use pocketmine\entity\Skin;
use pocketmine\network\mcpe\protocol\types\SkinAdapterSingleton;
use pocketmine\network\mcpe\protocol\types\SkinData;

class DisguiseInfo
{

    /** @var SkinData */
    private $skinData;

    /** @var string */
    private $name;

    /** @var Skin */
    private $oldSkin;

    /** @var string */
    private $oldName;

    public function __construct(Skin $skin, string $oldName)
    {
        // TODO: Generate random name.
        $this->name = "";
        // TODO: Generate skin data.
        $this->skinData = null;

        $this->oldSkin = $skin;
        $this->oldName = $oldName;
    }

    /**
     * @return Skin
     *
     * Gets the skin from skin data.
     */
    public function getSkin(): Skin
    {
        return SkinAdapterSingleton::get()->fromSkinData($this->skinData);
    }

    /**
     * @return string
     *
     * Gets the name of the disguise.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     *
     * Gets the old name of the player.
     */
    public function getOldName(): string
    {
        return $this->oldName;
    }

    /**
     * @return Skin
     *
     * Gets the old skin of the player.
     */
    public function getOldSkin(): Skin
    {
        return $this->oldSkin;
    }
}