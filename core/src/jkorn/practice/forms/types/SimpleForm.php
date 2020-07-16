<?php

declare(strict_types=1);

namespace jkorn\practice\forms\types;


use jkorn\practice\forms\Form;

/**
 * SimpleForm Class, majority was taken from FormAPI with some modifications.
 * @package jkorn\practice\forms\types
 */
class SimpleForm extends Form
{

    const IMAGE_TYPE_PATH = 0;
    const IMAGE_TYPE_URL = 1;

    /** @var string */
    private $content = "";

    private $labelMap = [];

    /**
     * @param callable $callable
     */
    public function __construct(?callable $callable) {
        parent::__construct($callable);
        $this->data["type"] = "form";
        $this->data["title"] = "";
        $this->data["content"] = $this->content;
    }

    /**
     * @param $data
     *
     * Processes the data.
     */
    public function processData(&$data) : void
    {
        $data = $this->labelMap[$data] ?? null;
    }

    /**
     * @param string $title
     *
     * Sets the title of the form.
     */
    public function setTitle(string $title): void
    {
        $this->data["title"] = $title;
    }

    /**
     * @return string
     *
     * Gets the title of the form.
     */
    public function getTitle(): string
    {
        return $this->data["title"];
    }

    /**
     * @return string
     *
     * Gets the content of the form.
     */
    public function getContent() : string
    {
        return $this->data["content"];
    }

    /**
     * @param string $content
     *
     * Sets the description of the form.
     */
    public function setContent(string $content) : void
    {
        $this->data["content"] = $content;
    }

    /**
     * @param string $text
     * @param int $imageType
     * @param string $imagePath
     * @param string $label
     */
    public function addButton(string $text, int $imageType = -1, string $imagePath = "", ?string $label = null) : void {
        $content = ["text" => $text];
        if($imageType !== -1) {
            $content["image"]["type"] = $imageType === 0 ? "path" : "url";
            $content["image"]["data"] = $imagePath;
        }
        $this->data["buttons"][] = $content;
        $this->labelMap[] = $label ?? count($this->labelMap);
    }
}