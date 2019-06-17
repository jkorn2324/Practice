<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-19
 * Time: 08:15
 */

declare(strict_types=1);

namespace practice\ranks;


use pocketmine\Player;
use pocketmine\utils\TextFormat;
use practice\player\PracticePlayer;
use practice\PracticeCore;
use practice\PracticeUtil;

class RankHandler
{
    public static $GUEST;
    public static $BUILDER;
    public static $YOUTUBE;
    public static $FAMOUS;
    public static $MODERATOR;
    public static $ADMIN;
    public static $OWNER;
    public static $DEV;

    private $allRanks = [];

    public function __construct()
    {
        self::$GUEST = new Rank('guest', 'Guest');
        self::$BUILDER = new Rank('builder', 'Builder');
        self::$YOUTUBE = new Rank('youtube', 'YouTube');
        self::$FAMOUS = new Rank('famous', 'Famous');
        self::$MODERATOR = new Rank('mod', 'Mod');
        self::$ADMIN = new Rank('admin', 'Admin');
        self::$OWNER = new Rank('owner', 'Owner');
        self::$DEV = new Rank('dev', 'Dev');

        $this->allRanks = [self::$GUEST, self::$BUILDER, self::$YOUTUBE, self::$FAMOUS, self::$MODERATOR, self::$ADMIN, self::$OWNER, self::$DEV];
    }

    public function setDefaultRank(PracticePlayer $player) : bool {
        return $this->setRank($player->getPlayer(), false, self::$GUEST);
    }

    public function setRank(Player $player, bool $sendMsg = false, Rank...$ranks) : bool {

        $name = $player->getName();

        $msg = $this->toMsg($ranks);

        if ($sendMsg === true and !is_null($msg)) $player->sendMessage($msg);

        $playerHandler = PracticeCore::getPlayerHandler();

        $data = $playerHandler->getPlayerData($name);
        $theRanks = $data["ranks"];
        if(is_array($theRanks)){
            $theRanks = [];
            foreach($ranks as $r)
                $theRanks[] = $r->getLocalizedName();
        }

        return $playerHandler->setPlayerData($name, 'ranks', $theRanks);
    }

    /**
     * @param $player
     * @return array|Rank[]
     */
    public function getRanksOf($player) : array {

        $result = [];

        $playerHandler = PracticeCore::getPlayerHandler();

        if($playerHandler->isPlayer($player)) {

            $p = $playerHandler->getPlayer($player);
            $data = $playerHandler->getPlayerData($p->getPlayerName());

            if(isset($data['ranks'])) {
                $ranksLocalized = $data["ranks"];
                $result = [];

                foreach ($ranksLocalized as $str) {
                    $r = $this->getRankFromName($str);
                    if ($r instanceof Rank)
                        $result[] = $r;
                }
            }
        }

        return $result;
    }

    public function hasRanks($player) : bool {
        return count($this->getRanksOf($player)) > 0;
    }

    /**
     * @param string $anyname
     * @return Rank|null
     */
    public function getRankFromName(string $anyname) {

        $result = null;

        foreach($this->allRanks as $rank){
            if($rank instanceof Rank){
                $localizedName = $rank->getLocalizedName();
                $name = $rank->getName();
                if($localizedName === $anyname){
                    $result = $rank;
                    break;
                } elseif ($name === $anyname){
                    $result = $rank;
                    break;
                }
            }
        }
        return $result;
    }

    private function listOfStaffRanks() : array {
        return [self::$MODERATOR, self::$ADMIN, self::$DEV, self::$OWNER];
    }

    private function isStaffRank(Rank $rank) : bool {
        return PracticeUtil::arr_contains_value($rank, $this->listOfStaffRanks());
    }

    public function hasRank($player, Rank $rank) : bool {

        $ranks = $this->getRanksOf($player);
        $size = count($ranks);
        $result = false;

        if($size > 0)
            $result = in_array($rank, $ranks);

        return $result;
    }

    public function hasStaffRank($player) : bool {
        $ranks = $this->getRanksOf($player);
        $size = count($ranks);
        $result = false;
        if($size > 0) {
            foreach($ranks as $rank) {
                if($this->isStaffRank($rank)){
                    $result = true;
                    break;
                }
            }
        }
        return $result;
    }

    public function hasFamousOrYTRank($player) : bool {

        $ranks = $this->getRanksOf($player);

        $size = count($ranks);
        $result = false;

        if($size > 0)
            $result = PracticeUtil::arr_contains_value(self::$YOUTUBE, $ranks) or PracticeUtil::arr_contains_value(self::$FAMOUS, $ranks);

        return $result;
    }

    public function getInvalidRank(array $ranks) {
        $result = null;
        foreach($ranks as $name) {
            $name = strval($name);
            if(is_null($this->getRankFromName($name))) {
                $result = $name;
                break;
            }
        }
        return $result;
    }

    public function areRanksValid(array $ranks) : bool {
        return is_null($this->getInvalidRank($ranks));
    }

    private function toMsg(array $ranks){
        
        $size = count($ranks);

        $message = null;

        if($size > 0){
            if($size === 1){
                $rank = $ranks[0];
                if($rank instanceof Rank) {
                    $name = $rank->getName();
                    $message = PracticeUtil::getMessage("general.rank.change-personal");
                    $message = strval(str_replace("%ranks%", "$name", $message));
                }
            } else {
                $ranksToList = "";
                $count = 0;
                $len = count($ranks) - 1;
                foreach($ranks as $rank){
                    if($rank instanceof Rank) {
                        $name = $rank->getLocalizedName();
                        $comma = ($count === $len ? "" : ", ");
                        $ranksToList = $ranksToList . $name . $comma;
                        $count++;
                    }
                }
                $message = PracticeUtil::getMessage("general.rank.change-personal");
                $message = strval(str_replace("%ranks%", "$ranksToList", $message));
            }
        }

        return $message;
    }

    public function getFormattedRanksOf(string $player) : string {

        $ranks = $this->getRanksOf($player);

        for($i = count($ranks) - 1; $i > -1; $i--) {
            $r = $ranks[$i];
            if($r instanceof Rank) {
                $localName = $r->getLocalizedName();
                if(strlen($localName) === 0) {
                    unset($ranks[$i]);
                    $ranks = array_values($ranks);
                }
            }
        }

        $ranks = array_values($ranks);

        $res = "";
        $len = count($ranks) - 1;
        $count = 0;
        foreach($ranks as $r) {
            if ($r instanceof Rank) {
                $plus = TextFormat::RESET . "" . TextFormat::WHITE . "+" . TextFormat::RESET;
                if ($count === $len) {
                    $plus = TextFormat::RESET . "";
                }
                $format = PracticeUtil::getRankFormatOf($r->getLocalizedName());
                $res .= $format . $plus;
                $count++;
            }
        }
        return $res;
    }

    public function getRankList() : string {
        $res = "List of ranks: ";
        $count = 0; $len = count($this->allRanks) - 1;
        foreach($this->allRanks as $rank) {
            if($rank instanceof Rank) {
                $comma = $count === $len ? "" : ", ";
                $res .= $rank->getName() . $comma;
            }
            $count++;
        }
        return $res;
    }
}