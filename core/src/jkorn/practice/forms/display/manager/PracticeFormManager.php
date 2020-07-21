<?php

declare(strict_types=1);

namespace jkorn\practice\forms\display;


use jkorn\practice\forms\display\manager\IFormDisplayManager;
use jkorn\practice\PracticeCore;
use pocketmine\Server;

class PracticeFormManager implements IFormDisplayManager
{

    const NAME = "practice.form.display";

    /** @var FormDisplay[] */
    private $displayForms = [];

    const FORM_PLAY_GAMES = "form.games.play";
    const FORM_SETTINGS_MENU = "form.settings.menu";
    const FORM_SETTINGS_BASIC = "form.settings.duels";
    const FORM_SETTINGS_BUILDER_MODE = "form.settings.builder";
    const FORM_PLAY_FFA = "form.FFA.play";

    /** @var string */
    private $resourcesFolder, $destinationFolder;

    /** @var Server */
    private $server;

    /** @var bool */
    private $loaded = false;

    public function __construct(PracticeCore $core)
    {
        $this->resourcesFolder = $core->getResourcesFolder() . "forms";
        $this->destinationFolder = $core->getDataFolder() . "forms";

        $this->server = $core->getServer();
    }

    /**
     * Loads the data needed for the manager.
     *
     * @param bool $async
     */
    public function load(bool $async): void
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
            $resource = fopen($this->resourcesFolder . "/forms.yml", "rb");
            stream_copy_to_stream($resource, $file = fopen($inputFile, "wb"));
            fclose($resource);
            fclose($file);
        }

        $this->loadFormDisplays($inputFile);

        $this->loaded = true;
    }

    /**
     * @param string $inputFile
     *
     * Loads the displays for all the forms.
     */
    private function loadFormDisplays(string $inputFile): void
    {
        $fileData = yaml_parse_file($inputFile);
        if(!is_array($fileData))
        {
            return;
        }

        // Gets the class name based on the types of forms inputted in forms.yml
        foreach ($fileData as $localizedName => $data) {

            $class = self::formLocalToClassName($localizedName);
            $namespacedClass = "jkorn\\practice\\forms\\display\\types\\{$class}";

            if(isset($data["class"]))
            {
                $namespacedClass = $data["class"];
            }

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
    private static function formLocalToClassName(string $name): string
    {
        $exploded = array_reverse(explode(".", $name));
        for ($i = 0; $i < count($exploded); $i++) {
            $exploded[$i] = ucfirst($exploded[$i]);
        }
        return implode($exploded, "");
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