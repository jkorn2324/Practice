<?php

declare(strict_types=1);

namespace jkorn\practice\misc;


use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use jkorn\practice\PracticeCore;

abstract class PracticeAsyncTask extends AsyncTask
{

    /**
     * @param Server $server
     *
     * Called when the function is complete.
     */
    public function onCompletion(Server $server)
    {
        $plugin = $server->getPluginManager()->getPlugin("Practice");
        if($plugin instanceof PracticeCore) {
            $this->doComplete($server);
        }
    }

    /**
     * Called in the onCompletion function
     * & used for tasks running in there.
     *
     * @param Server $server
     */
    abstract protected function doComplete(Server $server): void;

}