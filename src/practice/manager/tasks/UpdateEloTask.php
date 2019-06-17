<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-06-08
 * Time: 15:41
 */

declare(strict_types=1);

namespace practice\manager\tasks;

use pocketmine\scheduler\AsyncTask;
use practice\PracticeCore;

class UpdateEloTask extends AsyncTask
{

    private $sql;

    private $winner;
    private $winnerElo;
    private $loser;
    private $loserElo;

    private $queue;

    private $username;
    private $host;
    private $pass;
    private $port;
    private $database;

    public function __construct(string $winner, string $loser, int $winnerElo, int $loserElo, string $queue)
    {
        $this->winner = $winner;
        $this->loser = $loser;
        $this->winnerElo = $winnerElo;
        $this->loserElo = $loserElo;

        $this->queue = $queue;

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

        $this->sql->setElo($sql, $this->winner, $this->queue, $this->winnerElo);
        $this->sql->setElo($sql, $this->loser, $this->queue, $this->loserElo);
    }
}