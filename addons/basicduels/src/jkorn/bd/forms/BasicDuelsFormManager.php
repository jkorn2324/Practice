<?php

declare(strict_types=1);

namespace jkorn\bd\forms;


use jkorn\bd\BasicDuels;
use jkorn\practice\forms\display\FormDisplay;
use jkorn\practice\forms\display\manager\IFormDisplayManager;
use pocketmine\Server;

class BasicDuelsFormManager implements IFormDisplayManager
{

    const NAME = "basic.duels.display";

    // Form constants.
    const SELECTOR_FORM = "form.selector.type.duel";

    /** @var string */
    private $resourcesFolder, $destinationFolder;

    /** @var BasicDuels */
    private $core;

    /** @var Server */
    private $server;

    /** @var bool */
    private $loaded = false;

    /** @var FormDisplay[] */
    private $forms = [];

    public function __construct(BasicDuels $core)
    {
        $this->destinationFolder = $core->getDataFolder() . "forms";
        $this->resourcesFolder = $core->getResourcesFolder(). "forms";

        $this->core = $core;
        $this->server = $core->getServer();
    }

    /**
     * @param string $name
     * @return FormDisplay|null
     *
     * Gets the form from the display.
     */
    public function getForm(string $name): ?FormDisplay
    {
        if(isset($this->forms[$name]))
        {
            return $this->forms[$name];
        }
        return null;
    }

    /**
     * Loads the forms to the forms list.
     *
     * @param bool $async
     */
    public function load(bool $async): void
    {
        if(!is_dir($this->destinationFolder))
        {
            mkdir($this->destinationFolder);
        }

        if(!file_exists($inputFile = $this->destinationFolder . "/forms.yml"))
        {
            $resource = fopen($this->resourcesFolder . "/forms.yml", "rb");
            stream_copy_to_stream($resource, $file = fopen($inputFile, "wb"));
            fclose($resource);
            fclose($file);
        }

        $this->loadForms($inputFile);

        $this->loaded = true;
    }

    /**
     * @param string $inputFile
     *
     * Loads the forms based on the input file.
     */
    private function loadForms(string $inputFile): void
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

    /**
     * @return bool
     *
     * Determines if the form display manager has been loaded.
     */
    public function didLoad(): bool
    {
        return $this->loaded;
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
}