<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-29
 * Time: 09:28
 */

namespace practice\duels;


use practice\duels\groups\LoadedRequest;
use practice\duels\groups\Request;
use practice\PracticeCore;
use practice\PracticeUtil;

class IvsIHandler
{

    /* @var LoadedRequest[] */
    private $loadedRequests;
    /* @var Request[] */
    private $requests;

    public function __construct() {
        $this->requests = [];
        $this->loadedRequests = [];
    }

    public function loadRequest(string $player, string $requested) : void {

        $group = new LoadedRequest($player, $requested);

        $this->loadedRequests[] = $group;
    }

    public function isLoadingRequest($player) : bool {
        return !is_null($this->getLoadedRequest($player));
    }

    public function hasLoadedRequest($requestor, $requested) : bool {
        $result = false;

        $playerHandler = PracticeCore::getPlayerHandler();

        if(!is_null($this->getLoadedRequest($requestor)) and $playerHandler->isPlayerOnline($requested)) {
            $rq = $playerHandler->getPlayer($requested);
            $name = $rq->getPlayerName();
            $plLoaded = $this->getLoadedRequest($requestor);
            $result = $plLoaded->getRequested() === $name;
        }

        return $result;
    }

    /**
     * @param $player
     * @return LoadedRequest|null
     */
    public function getLoadedRequest($player) {

        $request = null;

        $name = PracticeUtil::getPlayerName($player);

        if(!is_null($name) and PracticeCore::getPlayerHandler()->isPlayerOnline($name)) {
            foreach($this->loadedRequests as $load) {
                if($name === $load->getRequestor()) {
                    $request = $load;
                    break;
                }
            }
        }

        /*if(PracticeCore::getPlayerHandler()->isPlayerOnline($player)) {
            $name = PracticeCore::getPlayerHandler()->getPlayer($player)->getPlayerName();
            foreach($this->loadedRequests as $load) {
                if($name === $load->getRequestor()) {
                    $request = $load;
                    break;
                }
            }
        }*/
        return $request;
    }

    private function indexOfLoadedRequest($object) : int {

        $index = array_search($object, $this->loadedRequests);

        if(is_bool($index) and $index === false)
            $index = -1;

        return $index;
    }

    public function cancelRequest($object) : void {

        if(PracticeCore::getPlayerHandler()->isPlayer($object)) {
            if($this->isLoadingRequest($object)) {
                $request = $this->getLoadedRequest($object);
                $index = $this->indexOfLoadedRequest($request);
                unset($this->loadedRequests[$index]);
                $this->loadedRequests = array_values($this->loadedRequests);
            }
        } elseif ($object instanceof LoadedRequest) {
            $index = $this->indexOfLoadedRequest($object);
            unset($this->loadedRequests[$index]);
            $this->loadedRequests = array_values($this->loadedRequests);
        } elseif ($object instanceof Request) {
            $index = $this->indexOfRequest($object);
            if(isset($this->requests[$index])) {
                unset($this->requests[$index]);
                $this->requests = array_values($this->requests);
            }
        }
    }

    public function sendRequest($requestor, $requested) : void {

        $playerHandler = PracticeCore::getPlayerHandler();

        if($playerHandler->isPlayerOnline($requestor) and $playerHandler->isPlayerOnline($requested)) {

            if($this->hasLoadedRequest($requestor, $requested)) {

                $request = $this->getLoadedRequest($requestor);

                $index = $this->indexOfLoadedRequest($request);

                if($request->hasQueue()) {

                    $group = new Request($requestor, $requested, $request->getQueue());

                    $req = $group->getRequested();
                    $pl = $group->getRequestor();
                    $rqMsg = PracticeUtil::str_replace(PracticeUtil::getMessage("general.duels.message"), ["%kit%" => $request->getQueue(), "%player%" => $group->getRequestorName()]);
                    $rqtrMsg = PracticeUtil::str_replace(PracticeUtil::getMessage("duels.1vs1.rq-sent"), ["%kit%" => $request->getQueue(), "%player%" => $group->getRequestedName(), "%ranked%" => " "]);
                    $req->sendMessage($rqMsg);
                    $pl->sendMessage($rqtrMsg);

                    $this->requests[] = $group;
                }

                unset($this->loadedRequests[$index]);
                $this->loadedRequests = array_values($this->loadedRequests);
            }
        }
    }

    public function hasPendingRequest($requested, $requestor) : bool {
        return !is_null($this->getRequest($requested, $requestor));
    }

    /**
     * @param $requested
     * @param $requestor
     * @return Request|null
     */
    public function getRequest($requested, $requestor) {

        $result = null;

        $playerHandler = PracticeCore::getPlayerHandler();

        if($playerHandler->isPlayerOnline($requested) and $playerHandler->isPlayerOnline($requestor)) {

            $requestorName = PracticeUtil::getPlayerName($requestor);

            $requestedName = PracticeUtil::getPlayerName($requested);

            foreach($this->requests as $request) {

                $rqName = $request->getRequestedName();
                $plName = $request->getRequestorName();

                if($rqName === $requestedName and $plName === $requestorName){
                    $result = $request;
                    break;
                }
            }
        }

        return $result;
    }

    private function indexOfRequest($request) : int {
        $key = array_search($request, $this->requests);
        if(is_bool($key) and $key === false)
            $key = -1;
        return $key;
    }

    public function acceptRequest($requested, $requestor) : void {

        if($this->hasPendingRequest($requested, $requestor)) {

            $request = $this->getRequest($requested, $requestor);

            $queue = $request->getQueue();

            $p = $request->getRequestorName();

            $pl = $request->getRequestor();

            $o = $request->getRequestedName();

            $pl->sendMessage(PracticeUtil::str_replace(PracticeUtil::getMessage("duels.1vs1.result-msg"), ["%player%" => $o, "%accept%" => "accepted", "%ranked% " => "", "%kit%" => $request->getQueue(), "%msg%" => ""]));

            PracticeCore::getDuelHandler()->setPlayersMatched($p, $o, true, $queue);

            $index = $this->indexOfRequest($request);

            unset($this->requests[$index]);

            $this->requests = array_values($this->requests);
        }
    }

    public function update() {

        $size = count($this->requests) - 1;

        for($i = $size; $i > -1; $i--) {

            if(isset($this->requests[$i])) {

                $request = $this->requests[$i];

                $update = $request->update();

                if ($update === true) {

                    $request->setExpired();

                    unset($this->requests[$i]);

                    $this->requests = array_values($this->requests);
                }
            }
        }
    }
}