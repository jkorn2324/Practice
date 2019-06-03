<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-18
 * Time: 09:46
 */

declare(strict_types=1);

namespace practice\player;


use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use practice\kits\Kit;
use practice\PracticeCore;
use practice\PracticeUtil;
use practice\ranks\RankHandler;

class PlayerHandler
{
    /* @var PracticePlayer[] */
    private $players;
    private $pendingDeviceData;
    private $playerFolderPath;
    private $closedInventoryIDs;

    private $leaderboards;

    public function __construct(PracticeCore $core)
    {
        $this->players = [];
        $this->pendingDeviceData = [];
        $this->closedInventoryIDs = [];
        $this->leaderboards = [];
        $this->initFiles($core);
    }

    private function initFiles(PracticeCore $core): void
    {

        $this->playerFolderPath = $core->getDataFolder() . '/players';

        if (!is_dir($this->playerFolderPath)) {
            mkdir($this->playerFolderPath);
        }
    }

    public function updateLeaderboards(): void
    {

        $result = [];

        $duelKits = PracticeCore::getKitHandler()->getDuelKitNames(true);

        foreach ($duelKits as $name) {

            $uncoloredName = PracticeUtil::getUncoloredString($name);

            $leaderboard = $this->getLeaderboardsFrom($uncoloredName);

            $result[$uncoloredName] = $leaderboard;
        }

        $global = $this->getLeaderboardsFrom();

        $result['global'] = $global;

        $this->leaderboards = $result;

        PracticeUtil::broadcastMsg(TextFormat::WHITE . '[' . TextFormat::RED . 'Zehox' . TextFormat::WHITE . ']' . TextFormat::BOLD . TextFormat::RED . ' Leaderboards are now up to date.');
    }

    public function getCurrentLeaderboards(): array
    {
        return $this->leaderboards;
    }

    public function getOpenChestID(Player $player): int
    {

        $result = 1;

        while (array_search($result, $this->closedInventoryIDs) !== false or !is_null($player->getWindow($result)))
            $result++;

        return $result;
    }

    public function setClosedInventoryID(int $id, Player $player): bool
    {

        $result = false;

        $index = array_search($id, $this->closedInventoryIDs);

        if (is_bool($index) and $index === false) $index = null;

        if (is_null($index)) {
            $this->closedInventoryIDs[$player->getName()] = $id;
            $result = true;
        }

        return $result;
    }

    public function setOpenInventoryID(Player $player): void
    {

        $name = $player->getName();

        $id = $this->getClosedChestID($player);

        if ($id !== -1) unset($this->closedInventoryIDs[$name]);
    }

    private function getClosedChestID(Player $player): int
    {

        $name = $player->getName();

        $id = -1;

        if (isset($this->closedInventoryIDs[$name]))
            $id = intval($this->closedInventoryIDs[$name]);

        return $id;
    }

    private function createPlayerData(string $player): void
    {

        $path = $this->playerFolderPath . "/$player.yml";

        $p = $this->players[$player];

        $address = $p->getPlayer()->getAddress();

        $encodedIP = PracticeCore::getIPHandler()->encodeIP($address);

        if (!file_exists($path)) {

            $file = fopen($path, 'wb');

            fclose($file);

            $elo = [];

            $kits = PracticeCore::getKitHandler()->getDuelKitNames(true);

            $size = count($kits);

            if ($size > 0) {
                foreach ($kits as $kit) {
                    $name = strval($kit);
                    $elo[$name] = 1000;
                }
            }

            $data = array(
                'aliases' => [$player],
                'stats' => array(
                    'kills' => 0,
                    'deaths' => 0,
                    'elo' => $elo
                ),
                'muted' => false,
                'ranks' => array(
                    RankHandler::$GUEST->getLocalizedName()
                ),
                'scoreboards-enabled' => true,
                'place-break' => false,
                'pe-only' => false,
                'ips' => [$encodedIP]
            );

            yaml_emit_file($path, $data);

        } else {

            $data = yaml_parse_file($path);

            $emit = false;

            if (!isset($data['scoreboards-enabled'])) {
                $data['scoreboards-enabled'] = true;
                $emit = true;
            }

            if (!isset($data['place-break'])) {
                $data['place-break'] = false;
                $emit = true;
            }

            if (!isset($data['pe-only'])) {
                $data['pe-only'] = false;
                $emit = true;
            }

            if (!isset($data['ips'])) {
                $data['ips'] = [$encodedIP];
                $emit = true;
            }

            $stats = $data['stats'];

            $elo = $stats['elo'];

            $kitHandler = PracticeCore::getKitHandler();

            //$duelKits = PracticeCore::getKitHandler()->getDuelKits();
            $duelKits = $kitHandler->getDuelKitNames();

            $keys = array_keys($elo);

            sort($keys);

            sort($duelKits);

            if ($keys !== $duelKits) {

                $difference = array_diff($duelKits, $keys);

                foreach ($difference as $kit) {

                    if ($kitHandler->isDuelKit($kit))
                        $elo[$kit] = 1000;
                    else {
                        if (isset($elo[$kit]))
                            unset($elo[$kit]);
                    }
                }

                $stats['elo'] = $elo;

                $data['stats'] = $stats;

                $emit = true;
            }

            if ($emit === true) yaml_emit_file($path, $data);
        }
    }

    public function enableScoreboard(string $player, bool $enable = true): void
    {
        $this->setPlayerData($player, 'scoreboards-enabled', $enable);
    }

    public function isScoreboardEnabled(string $player): bool
    {
        $result = true;
        $path = $this->playerFolderPath . "/$player.yml";
        if (file_exists($path)) {
            $data = yaml_parse_file($path, 0);
            if (is_array($data) and isset($data['scoreboards-enabled']))
                $result = boolval($data['scoreboards-enabled']);
        }
        return $result;
    }

    public function setPlaceNBreak(string $player, bool $enable = false): void
    {
        $this->setPlayerData($player, 'place-break', $enable);
    }

    public function canPlaceNBreak(string $player): bool
    {
        $result = false;
        $path = $this->playerFolderPath . "/$player.yml";
        if (file_exists($path)) {
            $data = yaml_parse_file($path, 0);
            if (is_array($data) and isset($data['place-break']))
                $result = boolval($data['place-break']);
        }
        return $result;
    }

    public function mutePlayer(string $name, bool $mute = true): bool
    {
        return $this->setPlayerData($name, 'muted', $mute);
    }

    public function unmutePlayer(string $name): bool
    {
        return $this->mutePlayer($name, false);
    }

    public function setPEOnlySetting(string $playerName, bool $peOnly = true): void
    {
        $this->setPlayerData($playerName, 'pe-only', $peOnly);
    }

    public function canQueuePEOnly(string $playerName): bool
    {

        $result = false;
        $path = $this->playerFolderPath . "/$playerName.yml";
        if (file_exists($path)) {
            $data = yaml_parse_file($path, 0);
            if (is_array($data) and isset($data['pe-only']))
                $result = boolval($data['pe-only']);
        }

        if ($result === true) {
            if ($this->isPlayerOnline($playerName)) {
                $p = $this->getPlayer($playerName);
                $result = $p->peOnlyQueue();
            }
        }

        return $result;
    }

    public function isPlayerMuted(string $name): bool
    {
        $path = $this->playerFolderPath . "/$name.yml";
        $result = false;
        if (file_exists($path)) {
            $data = yaml_parse_file($path, 0);
            if (is_array($data) and isset($data['muted'])) {
                $result = $data['muted'];
            }
        }
        return $result;
    }

    public function setPlayerData(string $player, string $key, $value): bool
    {
        $executed = true;
        $path = $this->playerFolderPath . "/$player.yml";
        if (file_exists($path)) {
            $data = yaml_parse_file($path, 0);
            if (is_array($data) and isset($data[$key])) {
                $data[$key] = $value;
                $executed = true;
            }
            yaml_emit_file($path, $data);
        } else {
            $this->createPlayerData($player);
            $executed = $this->setPlayerData($player, $key, $value);
        }
        return $executed;
    }

    public function getPlayerData($player): array
    {

        $name = null;

        $data = array();

        if (isset($player) and !is_null($player)) {
            if ($player instanceof Player) {
                $name = $player->getName();
            } else if ($player instanceof PracticePlayer) {
                $name = $player->getPlayerName();
            } else if (is_string($player)) {
                $name = $player;
            }
        }

        if (!is_null($name)) {

            $path = $this->playerFolderPath . "/$name.yml";
            if (file_exists($path)) {
                $d = yaml_parse_file($path, 0);
                if (is_array($d)) $data = $d;
            }
        }

        return $data;
    }

    public function putPendingPInfo(string $name, int $device, int $controls, int $clientID, string $deviceID): void
    {
        $this->pendingDeviceData[$name] = [
            'device' => $device,
            'controls' => $controls,
            'device-id' => $deviceID,
            'client-id' => $clientID
        ];
    }

    public function hasPendingPInfo($player): bool
    {
        return $this->getPendingPInfo($player) !== null;
    }

    public function removePendingPInfo($player): void
    {
        if ($this->hasPendingPInfo($player)) {
            $key = $this->getPendingDeviceKeyOf($player);
            if (!is_null($key) and is_string($key)) unset($this->pendingDeviceData[$key]);
            /*
             * $val = $this->getPendingDeviceOs($player);
             * unset($val);
             */
        }
    }

    private function getPendingDeviceKeyOf($player)
    {
        $result = null;
        $name = PracticeUtil::getPlayerName($player);
        if (!is_null($name) and is_string($name)) {
            if (isset($this->pendingDeviceData[$name])) {
                $val = $this->pendingDeviceData[$name];
                if (is_array($val)) $result = $name;
            }
        }
        return $result;
    }

    /**
     * @param $player
     * @return array|null
     */
    public function getPendingPInfo($player)
    {

        $name = null;
        $res = null;

        if (isset($player) and !is_null($player)) {
            if (is_string($player)) {
                $name = $player;
            } elseif ($player instanceof Player) {
                $name = $player->getName();
            } elseif ($player instanceof PracticePlayer) {
                $name = $player->getPlayerName();
            }
        }

        if (!is_null($name) and is_string($name)) {
            if (isset($this->pendingDeviceData[$name]))
                $res = $this->pendingDeviceData[$name];
        }
        return $res;
    }

    public function addPlayer(Player $player, int $deviceOs = -1): PracticePlayer
    {

        $p = new PracticePlayer($player, $deviceOs);

        $name = $player->getName();

        $this->players[$name] = $p;

        $this->createPlayerData($name);

        $rankHandler = PracticeCore::getRankHandler();

        if (!$rankHandler->hasRanks($p)) {
            $rankHandler->setDefaultRank($p);
        } else PracticeCore::getPermissionHandler()->updatePermissions($p);

        $address = $player->getAddress();

        $this->updateAliases($address, $name);

        return $p;
    }

    public function removePlayer($player): void
    {

        if ($this->isPlayer($player)) {

            $index = $this->getIndex($player);

            unset($this->players[$index]);
        }
    }

    public function isPlayerOnline($player): bool
    {

        $result = false;

        if ($this->isPlayer($player)) {
            $p = $this->getPlayer($player);
            $result = $p->isOnline();
        }

        return $result;
    }

    public function isPlayer($player): bool
    {
        return !is_null($this->getPlayer($player));
    }

    /**
     * @param $player -> Must be of types: string, PracticePlayer, or Player
     * @return PracticePlayer|null
     */
    public function getPlayer($player)
    {
        $res = null;
        if ($this->hasIndex($player)) {
            $index = $this->getIndex($player);
            $test = $this->players[$index];
            if ($test instanceof PracticePlayer) {
                $res = $test;
            }
        }
        return $res;
    }


    /**
     * @return PracticePlayer[]
     */
    public function getOnlinePlayers(): array
    {
        $res = [];

        $keys = array_keys($this->players);

        foreach ($keys as $key) {
            if (isset($this->players[$key])) {
                $player = $this->players[$key];
                if (!is_null($player) and $player instanceof PracticePlayer and $player->isOnline()) {
                    $res[] = $player;
                }
            }
        }
        return $res;
    }

    public function getPlayersInFights(): int
    {
        $count = 0;
        $duelHandler = PracticeCore::getDuelHandler();
        foreach ($this->getOnlinePlayers() as $player) {
            if ($player instanceof PracticePlayer) {
                if ($player->isInDuel()) {
                    $duel = $duelHandler->getDuel($player->getPlayerName());
                    if ($duel->isDuelRunning()) $count++;
                }
            }
        }
        return $count;
    }

    public function getOnlineStaff(): array
    {

        $res = [];

        $keys = array_keys($this->players);

        foreach ($keys as $key) {
            if (isset($this->players[$key])) {
                $player = $this->players[$key];
                if (!is_null($player) and $player instanceof PracticePlayer and $player->isOnline()) {
                    if ($this->isStaffMember($player)) $res[] = $player->getPlayerName();
                }
            }
        }

        return $res;
    }

    public function isAdmin($player): bool
    {

        $result = false;

        $name = PracticeUtil::getPlayerName($player);

        if (!is_null($name))

            $result = PracticeCore::getRankHandler()->hasRank($name, RankHandler::$ADMIN);

        return $result;

    }

    public function isMod($player): bool
    {

        $result = false;

        $name = PracticeUtil::getPlayerName($player);

        if (!is_null($name))

            $result = PracticeCore::getRankHandler()->hasRank($name, RankHandler::$MODERATOR);

        return $result;
    }

    public function isBuilder($player): bool
    {

        $result = false;

        $name = PracticeUtil::getPlayerName($player);

        if (!is_null($name))

            $result = PracticeCore::getRankHandler()->hasRank($name, RankHandler::$BUILDER);

        return $result;
    }

    public function isOwner($player): bool
    {

        $result = false;

        $name = PracticeUtil::getPlayerName($player);

        $rankHandler = PracticeCore::getRankHandler();

        if (!is_null($name))

            $result = $rankHandler->hasRank($name, RankHandler::$DEV) or $rankHandler->hasRank($name, RankHandler::$OWNER);

        return $result;
    }

    public function isStaffMember($player): bool
    {
        $result = false;
        $name = PracticeUtil::getPlayerName($player);

        if (!is_null($name)) {

            $result = PracticeCore::getRankHandler()->hasStaffRank($name);

            if ($result !== true and $this->isPlayerOnline($player)) {
                $p = $this->getPlayer($player);
                $result = $p->getPlayer()->isOp();
            }
        }
        return $result;
    }

    public function isContentCreator($player): bool
    {
        $result = false;
        $name = PracticeUtil::getPlayerName($player);

        if (!is_null($name))
            $result = PracticeCore::getRankHandler()->hasFamousOrYTRank($name);

        return $result;
    }

    private function hasIndex($player): bool
    {
        return $this->getIndex($player) !== '';
    }

    private function getIndex($player): string
    {

        $res = '';
        $name = null;

        if (isset($player) and !is_null($player)) {
            if (is_string($player)) {
                $name = $player;
            } elseif ($player instanceof Player) {
                $name = $player->getName();
            } elseif ($player instanceof PracticePlayer) {
                $name = $player->getPlayerName();
            } elseif ($player instanceof Entity) {
                $id = $player->getId();
                $p = PracticeUtil::getPlayerByID($id);
                if (!is_null($p)) $name = $p->getName();
            }
        }

        if (!is_null($name) and isset($this->players[$name]))
            $res = $name;

        return $res;
    }

    private function updateAliases(string $address, string $name): void
    {

        $data = $this->getPlayerData($name);

        if (isset($data['ips']) and isset($data['aliases'])) {

            $edited = false;

            $ipsEncoded = $data['ips'];

            $ipHandler = PracticeCore::getIPHandler();

            $ips = $ipHandler->decodeIPsFromArr($ipsEncoded);

            $size = count($ips);
            $path = $this->playerFolderPath . "/$name.yml";

            if ($size > 1 and !PracticeUtil::arr_contains_value($address, $ips)) {
                $ipsEncoded[] = $ipHandler->encodeIP($address);
                $data['ips'] = $ipsEncoded;
                $edited = true;
            }

            if ($edited === true) yaml_emit_file($path, $data);
        }
    }

    public function addEloKit(string $kit): void
    {
        $dir = $this->playerFolderPath;
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if (PracticeUtil::str_contains('.yml', strval($file))) {
                    $playerName = strval(str_replace('.yml', '', $file));
                    $data = $this->getPlayerData($playerName);
                    if (PracticeUtil::arr_contains_keys($data, 'stats')) {
                        $stats = $data['stats'];
                        if (PracticeUtil::arr_contains_keys($stats, 'elo')) {
                            $elo = $stats['elo'];
                            $elo[$kit] = 1000;
                            $stats['elo'] = $elo;
                        }
                        $data['stats'] = $stats;
                        yaml_emit_file($dir . "/$file", $data);
                    }
                }
            }
        }
    }

    public function removeEloKit(string $kit): void
    {
        $dir = $this->playerFolderPath;
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if (PracticeUtil::str_contains('.yml', strval($file))) {
                    $playerName = strval(str_replace('.yml', '', $file));
                    $data = $this->getPlayerData($playerName);
                    if (PracticeUtil::arr_contains_keys($data, 'stats')) {
                        $stats = $data['stats'];
                        if (PracticeUtil::arr_contains_keys($stats, 'elo')) {
                            $elo = $stats['elo'];
                            unset($elo[$kit]);
                            $stats['elo'] = $elo;
                        }
                        $data['stats'] = $stats;
                        yaml_emit_file($dir . "/$file", $data);
                    }
                }
            }
        }
    }

    private function getStatsFrom(string $player): array
    {

        $result = [];

        if ($player !== 'None') {

            $data = $this->getPlayerData($player);
            $stats = $data['stats'];

            if (is_array($stats))
                $result = $stats;

        }

        return $result;
    }


    /**
     * @param string $player
     * @param bool $form = true
     * @return string[];
     */
    public function getStats(string $player, bool $form = true)
    {

        $stats = $this->getStatsFrom($player);

        $title = '{color}' . 'Stats of ' . TextFormat::GOLD . $player . '{color}';

        $color = ($form === true) ? TextFormat::DARK_GRAY : TextFormat::GRAY;

        $title = PracticeUtil::str_replace($title, ['{color}' => $color]) . ($form === true) ? '' : ':';

        $k = intval($stats['kills']);

        $kills = TextFormat::GREEN . 'Kills' . TextFormat::WHITE . ": $k";

        $d = intval($stats['deaths']);

        $deaths = TextFormat::RED . 'Deaths' . TextFormat::WHITE . ": $d";

        $e = $stats['elo'];

        $elo = TextFormat::BLUE . 'Elo' . TextFormat::WHITE . ': {elo}';

        $restOfElo = '';

        $eloBeginTab = TextFormat::GOLD . '» ';

        $eloEndTab = TextFormat::GOLD . '«';

        $size = count($e) - 1;

        $count = 0;

        $keys = array_keys($e);

        foreach ($keys as $eloKit) {
            $eloKit = strval($eloKit);
            $eloOf = $e[$eloKit];
            $newLine = ($count === $size) ? '' : "\n";
            $restOfElo .= $eloBeginTab . TextFormat::AQUA . $eloKit . TextFormat::WHITE . " => $eloOf Elo " . $eloEndTab . $newLine;
            $count++;
        }

        $size = $size + 1;

        $replace = ($size > 0) ? "\n$restOfElo" : 'None';

        $elo = PracticeUtil::str_replace($elo, ['{elo}' => $replace]);

        $result = [
            'title' => $title,
            'kills' => $kills,
            'deaths' => $deaths,
            'elo' => $elo
        ];

        $lineSeparator = PracticeUtil::getLineSeparator($result);

        $len = strlen($lineSeparator) - 4;

        $lineSeparator = substr($lineSeparator, 0, $len);

        $result = [
            'firstSeparator' => $lineSeparator,
            'title' => $title,
            'secondSeparator' => $lineSeparator,
            'kills' => $kills,
            'deaths' => $deaths,
            'elo' => $elo,
            'thirdSeparator' => $lineSeparator
        ];

        return $result;
    }

    public function getEloFrom(string $player, string $kit): int
    {
        $stats = $this->getStatsFrom($player);
        return intval($stats['elo'][$kit]);
    }

    public function getKillsOf(string $player): int
    {
        $stats = $this->getStatsFrom($player);
        return intval($stats['kills']);
    }

    public function getDeathsOf(string $player): int
    {
        $stats = $this->getStatsFrom($player);
        return intval($stats['deaths']);
    }

    public function addKillFor(string $player): int
    {
        $kills = $this->getKillsOf($player) + 1;
        $this->updateStatsOf($player, 'kills', $kills);
        return $kills;
    }

    public function addDeathFor(string $player): int
    {
        $deaths = $this->getDeathsOf($player) + 1;
        $this->updateStatsOf($player, 'deaths', $deaths);
        return $deaths;
    }

    public function setEloOf(string $winner, string $loser, string $queue, int $winnerDevice, int $loserDevice): array
    {

        $result = ['winner' => 0, 'loser' => 0];

        $winnerElo = $this->getEloFrom($winner, $queue);
        $loserElo = $this->getEloFrom($loser, $queue);

        $kFactor = 32;

        $winnerExpectedScore = 1.0 / (1.0 + pow(10, floatval(($loserElo - $winnerElo) / 400)));
        $loserExpectedScore = abs(floatval(1.0 / (1.0 + pow(10, floatval(($winnerElo - $loserElo) / 400)))));

        $newWinnerElo = $winnerElo + intval($kFactor * (1 - $winnerExpectedScore));
        $newLoserElo = $loserElo + intval($kFactor * (0 - $loserExpectedScore));

        $winnerEloChange = $newWinnerElo - $winnerElo;
        $loserEloChange = abs($loserElo - $newLoserElo);

        if ($winnerDevice === PracticeUtil::WINDOWS_10 and $loserDevice !== PracticeUtil::WINDOWS_10)
            $loserEloChange = intval($loserEloChange * 0.9);
        else if ($winnerDevice !== PracticeUtil::WINDOWS_10 and $loserDevice === PracticeUtil::WINDOWS_10)
            $winnerEloChange = intval($winnerEloChange * 1.1);

        /*$result['winner'] = $winnerEloChange;
        $result['loser'] = $loserEloChange;*/

        $newWElo = $winnerElo + $winnerEloChange;
        $newLElo = $loserElo - $loserEloChange;

        if ($newLElo < 700) {
            $newLElo = 700;
            $loserEloChange = $loserElo - 700;
        }

        $result['winner'] = $winnerEloChange;
        $result['loser'] = $loserEloChange;

        $this->setElo($winner, $queue, $newWElo);
        $this->setElo($loser, $queue, $newLElo);

        return $result;
    }

    private function setElo(string $player, string $queue, int $value): void
    {
        $key = 'elo.' . $queue;
        $this->updateStatsOf($player, $key, $value);
    }

    private function updateStatsOf(string $player, string $key, $value): void
    {
        $stats = $this->getStatsFrom($player);
        if (PracticeUtil::str_contains('.', $key)) {
            $split = explode('.', $key);
            if (PracticeUtil::arr_contains_keys($stats, $split[0])) {
                $elo = $stats[$split[0]];
                if (PracticeUtil::arr_contains_keys($elo, $split[1])) {
                    $elo[$split[1]] = $value;
                }
                $stats[$split[0]] = $elo;
            }
        } else {
            if (PracticeUtil::arr_contains_keys($stats, $key)) {
                $stats[$key] = $value;
            }
        }
        $data = $this->getPlayerData($player);
        $data['stats'] = $stats;
        $file = '/' . $player . '.yml';
        yaml_emit_file($this->playerFolderPath . $file, $data);
    }

    public function resetStats(): void
    {

        $dir = $this->playerFolderPath;
        if (is_dir($dir)) {

            $files = scandir($dir);

            foreach ($files as $file) {
                $file = strval($file);
                if (PracticeUtil::str_contains('.yml', $file)) {
                    $playerName = str_replace('.yml', '', $file);
                    $data = $this->getPlayerData($playerName);
                    if (PracticeUtil::arr_contains_keys($data, 'stats')) {

                        $stats = $data['stats'];
                        $stats['kills'] = 0;
                        $stats['deaths'] = 0;
                        $elo = $stats['elo'];
                        $keys = array_keys($elo);

                        foreach ($keys as $key)
                            $elo[$key] = 1000;

                        $stats['elo'] = $elo;
                        $data = $this->getPlayerData($playerName);
                        $data['stats'] = $stats;
                        $f = '/' . $file;
                        yaml_emit_file($dir . $f, $data);
                    }
                }
            }
        }

        PracticeUtil::broadcastMsg('All player stats have been reset!');
    }


    /**
     * @param string $queue
     * @return string[]
     */
    private function getLeaderboardsFrom(string $queue = 'global'): array
    {

        $result = [];

        $format = "\n" . TextFormat::GRAY . '%spot%. ' . TextFormat::AQUA . '%player% ' . TextFormat::WHITE . '(%elo%)';

        $arr = $this->listEloForAll($queue);

        $sortedElo = $arr;
        /*$sortedElo = PracticeUtil::sort_array($arr);*/

        asort($sortedElo);

        $playerNames = array_keys($sortedElo);

        $size = count($sortedElo) - 1;

        $subtracted = ($size > 10) ? 9 : $size;

        $len = $size - $subtracted;

        for ($i = $size; $i >= $len; $i--) {
            $place = $size - $i;
            $name = strval($playerNames[$i]);
            $elo = intval($sortedElo[$name]);
            $string = PracticeUtil::str_replace($format, ['%spot%' => $place + 1, '%player%' => $name, '%elo%' => $elo]);
            $result[] = $string;
        }

        $size = count($result);

        if ($size > 10) {
            for ($i = $size; $i > 9; $i--) {
                if (isset($result[$i]))
                    unset($result[$i]);
            }
        }

        return $result;
    }

    private function listEloForAll(string $queue): array
    {

        $player_array = [];

        if (is_dir($this->playerFolderPath)) {

            $files = scandir($this->playerFolderPath);

            foreach ($files as $file) {

                $file = strval($file);

                if (PracticeUtil::str_contains('.yml', $file)) {

                    $name = strval(str_replace('.yml', '', $file));

                    $stats = $this->getStatsFrom($name);

                    $elo = $stats['elo'];

                    $resElo = 0;

                    if ($queue === 'global') {

                        $total = 0;

                        $count = count($elo);

                        $keys = array_keys($elo);

                        foreach ($keys as $q)
                            $total += intval($elo[$q]);

                        $resElo = intval($total / $count);

                    } else {

                        if (isset($elo[$queue]))
                            $resElo = intval($elo[$queue]);
                    }

                    $player_array[$name] = $resElo;
                }
            }
        }

        return $player_array;
    }
}