<?php

declare(strict_types=1);

namespace practice\forms\display;


use pocketmine\Player;
use practice\forms\Form;

abstract class FormDisplay
{

    /** @var string */
    protected $localizedName;

    /** @var FormDisplayText[] */
    protected $formData = [];

    public function __construct(string $localizedName, array $data)
    {
        $this->localizedName = $localizedName;
        $this->initData($data);
    }

    /**
     * @param array $data - The input data.
     * Initializes the form data.
     */
    abstract protected function initData(array &$data): void;

    /**
     * @param Player $player - The player we are sending the form to.
     *
     * Displays the form to the given player.
     */
    abstract public function display(Player $player): void;

    /**
     * @return string
     *
     * Gets the form display's localized name.
     */
    public function getLocalizedName(): string
    {
        return $this->localizedName;
    }

    abstract public static function decode(string $localized, array $data);

}