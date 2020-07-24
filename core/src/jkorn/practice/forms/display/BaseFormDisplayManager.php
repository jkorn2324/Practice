<?php

declare(strict_types=1);

namespace jkorn\practice\forms\display;


use jkorn\practice\forms\display\manager\AbstractFormDisplayManager;
use jkorn\practice\misc\AbstractManager;
use jkorn\practice\PracticeCore;
use jkorn\practice\forms\display\manager\PracticeFormManager;

class BaseFormDisplayManager extends AbstractManager
{

    /** @var AbstractFormDisplayManager[] */
    private $formManagers = [];

    public function __construct(PracticeCore $core)
    {
        $this->initDefaultFormManagers($core);
        parent::__construct(false);
    }

    /**
     * @param PracticeCore $core
     *
     * Initializes the default form managers.
     */
    private function initDefaultFormManagers(PracticeCore &$core): void
    {
        $this->registerFormDisplayManager(new PracticeFormManager($core));
    }

    /**
     * @param AbstractFormDisplayManager $manager
     * @param bool $load
     *
     * Registers the form display manager to the list of display managers.
     */
    public function registerFormDisplayManager(AbstractFormDisplayManager $manager, bool $load = false): void
    {
        if(!isset($this->formManagers[$manager->getLocalizedName()]))
        {
            $this->formManagers[$manager->getLocalizedName()] = $manager;

            if($load)
            {
                $manager->load();
            }
        }
    }

    /**
     * @param string $name
     * @return FormDisplay|null
     *
     * Gets the form from the name.
     */
    public function getForm(string $name): ?FormDisplay
    {
        foreach($this->formManagers as $manager)
        {
            $form = $manager->getForm($name);

            if($form !== null)
            {
                return $form;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @return AbstractFormDisplayManager|null
     *
     * Gets the form manager from the name.
     */
    public function getFormManager(string $name): ?AbstractFormDisplayManager
    {
        if(isset($this->formManagers[$name]))
        {
            return $this->formManagers[$name];
        }
        return null;
    }

    /**
     * Loads the data needed for the manager.
     *
     * @param bool $async
     */
    protected function load(bool $async = false): void
    {
        foreach($this->formManagers as $manager)
        {
            if(!$manager->didLoad())
            {
                $manager->load();
            }
        }
    }

    /**
     * Saves the data from the manager, do nothing here.
     *
     * @param bool $async
     */
    public function save(bool $async = false): void {}
}