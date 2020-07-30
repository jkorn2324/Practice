<?php

declare(strict_types=1);

namespace jkorn\practice\forms\display\manager;


use jkorn\practice\forms\display\FormDisplay;
use jkorn\practice\PracticeCore;
use pocketmine\Server;

class PracticeFormManager extends AbstractFormDisplayManager
{

    const NAME = "practice.form.display";

    const FORM_PLAY_GAMES = "form.games.play";
    const FORM_SETTINGS_MENU = "form.settings.menu";
    const FORM_SETTINGS_BASIC = "form.settings.basic";
    const FORM_SETTINGS_BUILDER_MODE = "form.settings.builder";
    const FORM_PLAY_FFA = "form.FFA.play";
    const FORM_SPECTATOR_SELECTION = "form.spectator.game.selection";

    /** @var PracticeCore */
    private $core;

    public function __construct(PracticeCore $core)
    {
        $this->core = $core;

        parent::__construct($core->getResourcesFolder() . "forms", $core->getDataFolder() . "forms");
    }

    /**
     * @param string $inputFile
     *
     * Loads the displays for all the forms.
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
                    $this->forms[$formDisplay->getLocalizedName()] = $formDisplay;
                }

            } catch (\Exception $e) {

                $this->server->getLogger()->alert($e->getTraceAsString());
            }
        }
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
     * @return string
     *
     * Gets the localized name of the form display manager.
     */
    public function getLocalizedName(): string
    {
        return self::NAME;
    }
}