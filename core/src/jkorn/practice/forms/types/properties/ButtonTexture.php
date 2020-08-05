<?php

declare(strict_types=1);

namespace jkorn\practice\forms\types\properties;


use jkorn\practice\misc\ISaved;

class ButtonTexture implements ISaved
{

    const TYPE_PATH = 0;
    const TYPE_URL = 1;

    /** @var int */
    private $imageType;

    /** @var string */
    private $path;

    public function __construct(int $imageType, string $path)
    {
        $this->imageType = $imageType;
        $this->path = $path;
    }

    /**
     * @return string
     *
     * Gets the texture path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return int
     *
     * Gets the texture path type (URL or PATH).
     */
    public function getImageType(): int
    {
        return $this->imageType;
    }

    /**
     * @return bool
     *
     * Determines if the button texture information is valid.
     */
    private function validate(): bool
    {
        return ($this->imageType === 0 || $this->imageType === 1) && $this->imageType !== "";
    }

    /**
     * @param array $array - The array we are importing the information.
     *
     * Imports the button texture information.
     */
    public function import(array &$array): void
    {
        if(!$this->validate())
        {
            return;
        }

        $array["image"]["type"] = $this->imageType === 0 ? "path" : "url";
        $array["image"]["data"] = $this->path;
    }

    /**
     * @return array
     *
     * Exports the button texture.
     */
    public function export(): array
    {
        return [
            "type" => $this->imageType,
            "path" => $this->path
        ];
    }

    /**
     * @param $data
     * @return ButtonTexture|null
     *
     * Decodes the data and creates a new button texture object.
     */
    public static function decode(&$data): ?ButtonTexture
    {
        if(is_array($data) && isset($data["type"], $data["path"]))
        {
            return new ButtonTexture(
                $data["type"],
                $data["path"]
            );
        }
        return null;
    }
}