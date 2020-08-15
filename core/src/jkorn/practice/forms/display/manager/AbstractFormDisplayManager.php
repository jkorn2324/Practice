<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2020-07-23
 * Time: 18:28
 */

declare(strict_types=1);

namespace jkorn\practice\forms\display\manager;

use pocketmine\Server;
use jkorn\practice\forms\display\FormDisplay;

abstract class AbstractFormDisplayManager
{

    /** @var string */
    protected $resourcesFolder;
    /** @var string */
    protected $destinationFolder;

    /** @var Server */
    protected $server;

    /** @var FormDisplay[] */
    protected $forms = [];

    /** @var bool */
    private $loaded = false;

    public function __construct(string $resourcesFolder, string $destinationFolder)
    {
        $this->resourcesFolder = $resourcesFolder;
        $this->destinationFolder = $destinationFolder;

        $this->server = Server::getInstance();
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
     * @return bool
     *
     * Determines if the form display manager has been loaded.
     */
    public function didLoad(): bool
    {
        return $this->loaded;
    }

    /**
     * Loads the forms to the forms list.
     */
    public function load(): void
    {
        if(!is_dir($this->destinationFolder))
        {
            mkdir($this->destinationFolder);
        }

        if (
            file_exists($this->resourcesFolder . "/README.md")
            && !file_exists($mdFile = $this->destinationFolder . "/README.md")
        ) {
            $mdResource = fopen($this->resourcesFolder . "/README.md", "rb");
            stream_copy_to_stream($mdResource, $file = fopen($mdFile, "wb"));
            fclose($mdResource);
            fclose($file);
        }

        if (
            file_exists($this->resourcesFolder . "/forms.yml")
            && !file_exists($inputFile = $this->destinationFolder . "/forms.yml")
        ) {
            $resource = fopen($this->resourcesFolder . "/forms.yml", "rb");
            stream_copy_to_stream($resource, $file = fopen($inputFile, "wb"));
            fclose($resource);
            fclose($file);
        }

        $this->loadFormDisplays($inputFile);
        $this->initInternalForms();
        $this->loaded = true;
    }

    /**
     * Initializes the internal forms to the display manager.
     */
    abstract protected function initInternalForms(): void;

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

        // Gets the class name based on the types of forms inputted in forms.yml
        foreach ($fileData as $localizedName => $data) {

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
     * @return string
     *
     * Gets the localized name of the form display manager.
     */
    abstract public function getLocalizedName(): string;
}