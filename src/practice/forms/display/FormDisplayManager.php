<?php

declare(strict_types=1);

namespace practice\forms\display;


use practice\misc\AbstractManager;
use practice\PracticeCore;

class FormDisplayManager extends AbstractManager
{

    /** @var FormDisplay[] */
    private $displayForms = [];

    const FORM_SETTINGS_MENU = "form.settings.menu";
    const FORM_SETTINGS_BASIC = "form.settings.basic";
    const FORM_SETTINGS_BUILDER_MODE = "form.settings.builder";
    const FORM_PLAY_FFA = "form.play.FFA";

    /** @var string */
    private $resourcesFolder, $destinationFolder;

    public function __construct(PracticeCore $core)
    {
        $this->resourcesFolder = $core->getResourcesFolder() . "forms";
        $this->destinationFolder = $core->getDataFolder() . "forms";

        parent::__construct($core, false);
    }

    /**
     * Loads the data needed for the manager.
     *
     * @param bool $async
     */
    protected function load(bool $async = false): void
    {
        if (!is_dir($this->destinationFolder)) {
            mkdir($this->destinationFolder);
        }

        if (!file_exists($mdFile = $this->destinationFolder . "/README.md")) {
            $mdResource = fopen($this->resourcesFolder . "/README.md", "rb");
            stream_copy_to_stream($mdResource, $file = fopen($mdFile, "wb"));
            fclose($mdResource);
            fclose($file);
        }

        if (!file_exists($inputFile = $this->destinationFolder . "/forms.yml")) {
            $destination = $inputFile;
            $resource = fopen($inputFile = $this->resourcesFolder . "/forms.yml", "rb");
            stream_copy_to_stream($resource, $file = fopen($destination, "wb"));
            fclose($resource);
            fclose($file);
        }

        $this->loadFormDisplays($inputFile);
    }

    /**
     * @param string $inputFile
     *
     * Loads the displays for all the forms.
     */
    private function loadFormDisplays(string $inputFile): void
    {
        $fileData = yaml_parse_file($inputFile);
        foreach ($fileData as $localizedName => $data) {

            $class = self::localToClassName($localizedName);

            if (!class_exists($class) || !is_subclass_of($class, FormDisplay::class))
            {
                continue;
            }

            // Loads the display forms to the array based on reflection class
            // information.
            try {
                $reflection = new \ReflectionClass($class);
                $method = $reflection->getMethod("decode");
                if (!$method instanceof \ReflectionMethod) {
                    continue;
                }

                $formDisplay = $method->invokeArgs(null, [$localizedName, $data]);
                if($formDisplay instanceof FormDisplay)
                {
                    $this->displayForms[$formDisplay->getLocalizedName()] = $formDisplay;
                }

            } catch (\Exception $e) {

                $this->server->getLogger()->alert($e->getTraceAsString());
            }
        }
    }

    /**
     * Saves the data from the manager, not needed here.
     *
     * @param bool $async
     */
    public function save(bool $async = false): void {}

    /**
     * @param string $localized
     * @return FormDisplay|null
     *
     * Gets the form display from its localized name.
     */
    public function getForm(string $localized): ?FormDisplay
    {
        if(isset($this->displayForms[$localized]))
        {
            return $this->displayForms[$localized];
        }
        return null;
    }

    /**
     * @param string $name
     * @return string
     *
     * Converts the localized name to a class name.
     */
    private static function localToClassName(string $name): string
    {
        $exploded = array_reverse(explode(".", $name));
        for ($i = 0; $i < count($exploded); $i++) {
            $exploded[$i] = ucfirst($exploded[$i]);
        }
        return implode($exploded, "");
    }
}