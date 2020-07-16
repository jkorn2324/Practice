<?php

declare(strict_types=1);

namespace jkorn\practice\player\ranks;


use jkorn\practice\misc\AbstractManager;
use jkorn\practice\PracticeCore;

class RankManager extends AbstractManager
{

    /** @var string */
    private $ranksDirectory;

    public function __construct(PracticeCore $core, bool $loadAsync)
    {
        $this->ranksDirectory = $core->getDataFolder() . "ranks/";

        parent::__construct($core, $loadAsync);
    }

    /**
     * Loads the data needed for the manager.
     *
     * @param bool $async
     */
    protected function load(bool $async = false): void
    {
        // TODO: Implement load() method.
    }

    /**
     * Saves the data from the manager.
     *
     * @param bool $async
     */
    public function save(bool $async = false): void
    {
        // TODO: Implement save() method.
    }
}