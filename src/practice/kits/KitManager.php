<?php

declare(strict_types=1);

namespace practice\kits;


use pocketmine\Server;
use practice\misc\AbstractManager;
use practice\misc\PracticeAsyncTask;
use practice\PracticeCore;

class KitManager extends AbstractManager
{

    /** @var string */
    private $kitDirectory;
    /** @var Kit[] */
    private $kits;

    /** @var Kit[] */
    private $deletedKits;

    public function __construct(PracticeCore $core)
    {
        $this->kitDirectory = $core->getDataFolder() . "kits/";
        $this->kits = [];
        $this->deletedKits = [];

        parent::__construct($core, false);
    }

    /**
     * Loads the data needed for the manager.
     *
     * @param bool $async
     */
    protected function load(bool $async = false): void
    {
        if($async)
        {
            $this->server->getAsyncPool()->submitTask(new class($this->kitDirectory) extends PracticeAsyncTask {

                /** @var string */
                private $kitDirectory;

                public function __construct(string $kitDirectory)
                {
                    $this->kitDirectory = $kitDirectory;
                }

                /**
                 * Actions to execute when run
                 *
                 * @return void
                 */
                public function onRun()
                {
                    if(!is_dir($this->kitDirectory)) {
                        mkdir($this->kitDirectory);
                        $this->setResult(["kits" => []]);
                        return;
                    }

                    $files = scandir($this->kitDirectory);
                    if(count($files) <= 0)
                    {
                        $this->setResult(["kits" => []]);
                        return;
                    }

                    $kits = [];
                    foreach($files as $file) {
                        if(strpos($file, ".json") === false) {
                            continue;
                        }
                        $contents = json_decode(file_get_contents($this->kitDirectory . "/" . $file), true);
                        $name = str_replace(".json", "", $file);
                        $kits[$name] = $contents;
                    }

                    $this->setResult(["kits" => $kits]);
                }

                /**
                 * @param Server $server
                 * Called if the plugin is enabled.
                 */
                public function doComplete(Server $server): void
                {
                    $results = $this->getResult();
                    if($results !== null && isset($results["kits"]))
                    {
                        $kits = $results["kits"];
                        if(count($kits) <= 0) {
                            return;
                        }

                        PracticeCore::getKitManager()->postLoad($kits);
                    }
                }
            });
            return;
        }

        // This section runs when its not async.

        if(!is_dir($this->kitDirectory))
        {
            mkdir($this->kitDirectory);
            return;
        }

        $files = scandir($this->kitDirectory);
        if(count($files) <= 0)
        {
            return;
        }

        $kits = [];
        foreach($files as $file) {
            if(strpos($file, ".json") === false) {
                continue;
            }
            $contents = json_decode(file_get_contents($this->kitDirectory . "/" . $file), true);
            $name = str_replace(".json", "", $file);
            $kits[$name] = $contents;
        }

        $this->postLoad($kits);
    }

    /**
     * @param $data
     *
     * Loads the kits accordingly.
     */
    public function postLoad($data): void
    {
        foreach($data as $kitName => $kitData) {
            $kit = Kit::decode((string)$kitName, $kitData);
            if($kit instanceof Kit) {
                $this->kits[strtolower($kit->getName())] = $kit;
            }
        }
    }

    /**
     * Saves the data from the manager.
     *
     * @param bool $async
     */
    public function save(bool $async = false): void
    {
        if($async) {
            return;
        }

        foreach($this->kits as $localized => $kit) {

            $file = $this->kitDirectory . "{$kit->getName()}.json";

            if(isset($this->deletedKits[$localized])) {
                $this->handleDelete($file, $kit);
            } else {

                if(!file_exists($file)) {
                    $file = fopen($file, "w");
                    fclose($file);
                }

                file_put_contents(
                    $file,
                    json_encode($kit->export())
                );
            }
        }
    }

    /**
     * @param $kit
     *
     * Deletes the kit from the list.
     */
    public function delete($kit): void
    {
        if(!$kit instanceof Kit && !is_string($kit)) {
            return;
        }

        $name = $kit instanceof Kit ? $kit->getName() : $kit;
        $lowercase = strtolower($name);
        if(isset($this->kits[$lowercase])) {
            $this->deletedKits[$lowercase] = $this->kits[$lowercase];
        }
    }

    /**
     * @param Kit $kit
     * @return bool
     *
     * Adds the kit to the list.
     */
    public function add(Kit $kit): bool
    {
        if(isset($this->kits[$localized = strtolower($kit->getName())])) {

            if(!isset($this->deletedKits[$localized])) {
                return false;
            }

            unset($this->deletedKits[$localized]);
        }

        $this->kits[$localized] = $kit;

        // TODO: Add the kit to all arenas.

        return true;
    }

    /**
     * @param string $kit
     * @return bool
     *
     * Determines if the kit is deleted or not.
     */
    public function isDeleted(string $kit): bool
    {
        return isset($this->deletedKits[strtolower($kit)]);
    }

    /**
     * @param string $kit
     * @param bool $deleted - If true, the function then checks if kit is deleted & returns null if it is.
     * @return Kit|null
     *
     * Gets the kit from the list.
     */
    public function get(string $kit, bool $deleted = false): ?Kit
    {
        $localized = strtolower($kit);
        if(isset($this->kits[$localized])) {
            $kit = $this->kits[$localized];
            if($deleted && isset($this->deletedKits[$localized])) {
                return null;
            }
            return $kit;
        }
        return null;
    }

    /**
     * @var bool $deleted - If true, it includes the kits that are deleted.
     * @return array|Kit[]
     *
     * Lists all kits.
     */
    public function getAll(bool $deleted = false)
    {
        if($deleted) {
            return $this->kits;
        }

        $output = [];
        foreach($this->kits as $localizedName => $kit)
        {
            if(isset($this->deletedKits[$localizedName])) {
                continue;
            }
            $output[$localizedName] = $kit;
        }

        return $output;
    }

    /**
     * @param string $file
     * @param Kit $kit
     *
     * Called when the kit manager deletes a kit via the save
     * function non async.
     */
    private function handleDelete(string $file, Kit $kit): void
    {
        if(file_exists($file)) {
            unlink($file);
        }

        // TODO: Remove kit from all arenas.
    }
}