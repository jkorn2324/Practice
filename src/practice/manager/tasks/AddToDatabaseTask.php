<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-06-08
 * Time: 15:21
 */

declare(strict_types=1);

namespace practice\manager\tasks;


use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use practice\PracticeCore;

class AddToDatabaseTask extends AsyncTask
{

    private $player;
    private $sql;

    private $username;
    private $host;
    private $pass;
    private $port;
    private $database;

    public function __construct(string $player)
    {
        $this->player = $player;
        $this->sql = PracticeCore::getMysqlHandler();
        $this->username = $this->sql->getUsername();
        $this->host = $this->sql->getHost();
        $this->pass = $this->sql->getPassword();
        $this->port = $this->sql->getPort();
        $this->database = $this->sql->getDatabaseName();
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun()
    {
        $sql = new \mysqli($this->host, $this->username, $this->pass, $this->database, $this->port);

        $this->sql->addPlayerToDatabase($this->player, $sql);
    }

    public function onCompletion(Server $server)
    {
        parent::onCompletion($server);
    }
}