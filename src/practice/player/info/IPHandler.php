<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-06-01
 * Time: 22:08
 */

declare(strict_types=1);

namespace practice\player\info;

use pocketmine\utils\Config;
use practice\PracticeCore;
use practice\PracticeUtil;

class IPHandler
{
    /* @var Config */
    private $config;

    private $safe_ips = [];
    private $blocked_ips = [];

    public function __construct(PracticeCore $core) {
        $this->initConfig($core->getDataFolder());
    }

    private function initConfig(string $dataFolder) : void {

        $path = $dataFolder . '/ip-storage.yml';

        $data = [
            'api-key' => 'key',
            'safe-ips' => [],
            'blocked-ips' => [],
        ];

        $this->config = new Config($path, Config::YAML, $data);

        $safeIps = $this->config->get('safe-ips');
        $blockedIps = $this->config->get('blocked-ips');

        foreach($safeIps as $ip) {
            $ip = strval($ip);
            $ip = $this->decodeIP($ip);
            $this->safe_ips[$ip] = true;
        }

        foreach($blockedIps as $ip) {
            $ip = strval($ip);
            $ip = $this->decodeIP($ip);
            $this->blocked_ips[$ip] = true;
        }
    }

    public function hasAPIKey() : bool {
        $key = $this->config->get('api-key');
        return $key !== 'key';
    }

    /* @return string */
    private function getKey() {
        return strval($this->config->get('api-key'));
    }

    private function getConfig() : Config {
        return $this->config;
    }

    public function isIpSafe(string $ip, bool $checkStrict = false) : bool {

        $key = $this->getKey();

        $result = true;

        if($this->hasAPIKey()) {

            if(isset($this->blocked_ips[$ip]))
                return false;
            elseif (isset($this->safe_ips[$ip]))
                return true;
            else {

                $curl = curl_init();

                curl_setopt_array($curl, [
                    CURLOPT_URL => "http://v2.api.iphub.info/ip/{$ip}",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => ["X-Key: {$key}"]
                ]);

                $executed = curl_exec($curl);
                curl_close($curl);

                $info = json_decode($executed, true);

                if (isset($info) and isset($info['block'])) {
                    $block = $info['block'];
                    if ($checkStrict === true) $result = false;
                    else $result = $block === 0;
                }
            }
        }

        if($result === true)
            $this->setIPSafe($ip);
        else $this->setIPBlocked($ip);

        return $result;
    }

    private function setIPSafe(string $ip) : void {
        $this->safe_ips[$ip] = true;
        $config = $this->getConfig();
        $safeIps = $config->get('safe-ips');
        if(!PracticeUtil::arr_contains_value($ip, $safeIps)) {
            $safeIps[] = $this->encodeIP($ip);
            $config->set('safe-ips', $safeIps);
            $config->save();
        }
    }

    private function setIPBlocked(string $ip) : void {
        $this->blocked_ips[$ip] = true;
        $config = $this->getConfig();
        $blockedIps = $config->get('blocked-ips');
        if(!PracticeUtil::arr_contains_value($ip, $blockedIps)) {
            $blockedIps[] = $this->encodeIP($ip);
            $config->set('blocked-ips', $blockedIps);
            $config->save();
        }
    }

    public function encodeIP(string $ip) : string {
        $split = explode('.', $ip);
        $res = '';
        $count = 0;
        foreach($split as $part) {
            $part = strval($part);
            $first = rand(0, 9);
            $second = rand(0, 9);
            $dot = ($count === 3) ? '' : '.';
            $res .= $first . $part . $second . $dot;
            $count++;
        }
        return base64_encode($res);
    }

    public function decodeIP(string $encoded) : string {
        $decoded = base64_decode($encoded);
        $split = explode('.', $decoded);
        $res = '';
        $count = 0;
        foreach($split as $part) {
            $part = strval($part);
            $len = strlen($part);
            $dot = ($count === 3) ? '' : '.';
            $newPart = substr($part, 1, $len - 1);
            $res .= $newPart . $dot;
            $count++;
        }
        return $res;
    }

    /**
     * @param array $ipsEncoded
     * @return array|string[]
     */
    public function decodeIPsFromArr(array $ipsEncoded) : array {
        $ips = [];
        foreach($ipsEncoded as $ip)
            $ips[] = $this->decodeIP($ip);
        return $ips;
    }
}