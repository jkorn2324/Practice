<?php

declare(strict_types=1);

namespace jkorn\bd\arenas;


use jkorn\bd\BasicDuels;
use jkorn\bd\BasicDuelsManager;
use jkorn\practice\arenas\PracticeArenaManager;
use jkorn\practice\forms\IPracticeForm;
use jkorn\practice\forms\types\properties\ButtonTexture;

class ArenaManager extends PracticeArenaManager
{

    /** @var string */
    private $arenasFile;

    /** @var PreGeneratedDuelArena[] */
    private $arenas;

    /** @var array */
    private $openArenas;

    public function __construct(BasicDuels $basicDuels, BasicDuelsManager $parent)
    {
        $this->arenas = [];
        $this->openArenas = [];

        parent::__construct($basicDuels->getDataFolder() . "arenas/", $parent, false);

        $this->arenasFile = $this->getDirectory() . "duels.json";
    }

    /**
     * Used to load the arenas of the arena manager.
     */
    protected function onLoad(): void
    {
        if(!file_exists($this->arenasFile))
        {
            $file = fopen($this->arenasFile, "w");
            fclose($file);
        }
        else
        {
            $contents = json_decode(file_get_contents($this->arenasFile), true);
            if(!is_array($contents))
            {
                return;
            }

            foreach($contents as $arenaName => $data)
            {
                $arena = PreGeneratedDuelArena::decode($arenaName, $data);
                if($arena !== null)
                {
                    $this->arenas[$arena->getName()] = $arena;
                    $this->openArenas[$arena->getName()] = true;
                }
            }
        }
    }

    /**
     * @param $arena - The arena to add to the list.
     * @param bool $override
     *
     * @return bool - Determines whether or not the arena has been successfully added.
     *
     * Adds an arena to the practice arena manager.
     */
    public function addArena($arena, bool $override = false): bool
    {
        // TODO: Implement addArena() method.
        return true;
    }

    /**
     * @param string $name - The name of the arena.
     * @return mixed
     *
     * Gets the arena from the arena list.
     */
    public function getArena(string $name)
    {
        if(isset($this->arenas[strtolower($name)]))
        {
            return $this->arenas[strtolower($name)];
        }
        return null;
    }

    /**
     * @param callable|null $filter - This filters the based on a callable,
     *        the callable should return a boolean and should contain an arena parameter.
     *
     * @return array|PreGeneratedDuelArena[]
     *
     * Gets the arenas from the arena list.
     */
    public function getArenas(?callable $filter = null)
    {
        if($filter !== null)
        {
            return array_filter($this->arenas, $filter);
        }

        return $this->arenas;
    }

    /**
     * @param $arena - The arena to delete from the list.
     *
     * Deletes the arena from the arena list.
     */
    public function deleteArena($arena): void
    {
        // TODO: Implement deleteArena() method.
    }

    /**
     * @param PreGeneratedDuelArena $arena
     *
     * Closes the arena so it can't be used.
     */
    public function close(PreGeneratedDuelArena $arena): void
    {
        if(isset($this->openArenas[$arena->getLocalizedName()]))
        {
            unset($this->openArenas[$arena->getLocalizedName()]);
        }
    }

    /**
     * @param PreGeneratedDuelArena $arena
     *
     * Opens the arena so it can be used again.
     */
    public function open(PreGeneratedDuelArena $arena): void
    {
        $this->openArenas[$arena->getLocalizedName()] = true;
    }

    /**
     * @return PreGeneratedDuelArena|null
     *
     * Gets a random arena from the open arenas list.
     */
    public function randomArena(): ?PreGeneratedDuelArena
    {
        if(count($this->openArenas) <= 0)
        {
            return null;
        }

        $randomKey = array_keys($this->openArenas)[mt_rand(0, count($this->openArenas) - 1)];
        if(isset($this->arenas[$randomKey]))
        {
            return $this->arenas[$randomKey];
        }
        return null;
    }

    /**
     * Used to save the arenas.
     *
     * @return bool - Return true if the arenas have successfully been saved, false otherwise.
     */
    protected function onSave(): bool
    {
        $exported = [];
        foreach($this->arenas as $arena)
        {
            $exported[$arena->getName()] = $arena->export();
        }
        file_put_contents($this->arenasFile, json_encode($exported));
        return true;
    }

    /**
     * @return ButtonTexture|null
     *
     * Gets the form display texture.
     */
    public function getFormButtonTexture(): ?ButtonTexture
    {
        // TODO: Implement getFormButtonTexture() method.
        return null;
    }

    /**
     * @return IPracticeForm|null
     *
     * Gets the menu used to edit the arenas.
     */
    public function getArenaEditorMenu(): ?IPracticeForm
    {
        // TODO: Implement getArenaEditorMenu() method.
        return null;
    }
}