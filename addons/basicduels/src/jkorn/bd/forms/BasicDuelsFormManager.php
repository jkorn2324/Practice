<?php

declare(strict_types=1);

namespace jkorn\bd\forms;


use jkorn\bd\BasicDuels;
use jkorn\practice\forms\display\FormDisplay;
use jkorn\practice\forms\display\manager\AbstractFormDisplayManager;


class BasicDuelsFormManager extends AbstractFormDisplayManager
{

    const NAME = "basic.duels.display";

    // Form constants.
    const TYPE_SELECTOR_FORM = "form.selector.type.duel";
    const KIT_SELECTOR_FORM = "form.selector.type.kit";

    /** @var BasicDuels */
    private $core;

    public function __construct(BasicDuels $core)
    {
        $this->core = $core;

        parent::__construct($core->getResourcesFolder() . "forms", $core->getDataFolder() . "forms");
    }

    /**
     * @return string
     *
     * Gets the localized name of the form display manager.
     */
    public function getLocalizedName(): string
    {
        return self::NAME;
    }

    /**
     * @param string $inputFile
     *
     * Loads the form displays from the input file.
     */
    protected function loadFormDisplays(string &$inputFile): void
    {
        $fileData = yaml_parse_file($inputFile);
        if(!is_array($fileData))
        {
            return;
        }

        foreach($fileData as $localizedName => $data)
        {
            if(!isset($data["class"]))
            {
                continue;
            }

            $namespacedClass = $data["class"];
            if (!class_exists($namespacedClass) || !is_subclass_of($namespacedClass, FormDisplay::class))
            {
                continue;
            }

            // Loads the display forms to the array based on reflection class
            // information.
            try {

                $reflection = new \ReflectionClass($namespacedClass);
                $method = $reflection->getMethod("decode");
                $formDisplay = $method->invokeArgs(null, [$localizedName, $data]);
                if($formDisplay instanceof FormDisplay)
                {
                    $this->forms[$formDisplay->getLocalizedName()] = $formDisplay;
                }

            } catch (\Exception $e) {

                $this->server->getLogger()->alert($e->getTraceAsString());
            }
        }
    }
}