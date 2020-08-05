<?php

declare(strict_types=1);

namespace jkorn\practice\forms\types;


use jkorn\practice\forms\Form;
use jkorn\practice\forms\types\properties\ButtonTexture;

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
     * @param mixed...$args - The arguments for the simple form.
     *
     * Adds a button to the simple form based on the arguments.
     * These are the valid method calls that can be used:
     * - addButton(string text);
     * - addButton(string text, ButtonTexture texture);
     * - addButton(string text, ButtonTexture texture, string label);
     * - addButton(string text, int imageType, string path);
     * - addButton(string text, int imageType, string path, string label);
     * - addButton(string text, string label);
     * - addButton(string text, string label, ButtonTexture texture);
     */
    public function addButton(string $text, ...$args) : void {

        $content = ["text" => $text];

        if(isset($args[0]))
        {
            $firstArgument = $args[0];

            if($firstArgument instanceof ButtonTexture)
            {
                $firstArgument->import($content);
                if
                (
                    isset($args[1])
                    && (is_string(($secondArgument = $args[1]))
                    || is_integer($secondArgument))
                )
                {
                    $label = $secondArgument;
                }
            }
            elseif (
                is_integer($firstArgument)
                && isset($args[1])
                && is_string(($secondArgument = $args[1]))
            )
            {
                $texture = new ButtonTexture(intval($firstArgument), strval($secondArgument));
                $texture->import($content);

                if(
                    isset($args[3])
                    && (is_int($thirdArgument = $args[3])
                    || is_string($thirdArgument))
                )
                {
                    $label = $thirdArgument;
                }
            }
            elseif (is_string($firstArgument))
            {
                $label = $firstArgument;

                if
                (
                    isset($args[1])
                    && ($secondArgument = $args[1]) !== null
                    && $secondArgument instanceof ButtonTexture
                )
                {
                    $secondArgument->import($content);
                }
            }
        }

        $this->data["buttons"][] = $content;
        $this->labelMap[] = isset($label) ? $label : count($this->labelMap);
    }
}