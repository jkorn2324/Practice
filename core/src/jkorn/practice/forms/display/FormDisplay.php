<?php

declare(strict_types=1);

namespace jkorn\practice\forms\display;


use jkorn\practice\forms\IPracticeForm;

abstract class FormDisplay implements IPracticeForm
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