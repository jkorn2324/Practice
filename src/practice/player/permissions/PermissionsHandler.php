<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-05-21
 * Time: 18:53
 */

declare(strict_types=1);

namespace practice\player\permissions;

use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use practice\player\PracticePlayer;
use practice\PracticeCore;
use practice\PracticeUtil;

class PermissionsHandler
{

    public const PERMISSION_PLACE_BREAK = 'practice.permission.build-break';

    /* @var Config */
    private $config;

    private $configPath;

    public function __construct(PracticeCore $core) {
        $path = $core->getDataFolder();
        $this->configPath = $path . '/permissions.yml';
        //$this->initConfig();
    }

    public function initPermissions() : void {
        $this->initConfig();
    }

    private function initConfig() : void {

        $array = [
            'content-creators' => [],
            'builders' => [],
            'mods' => [],
            'admins' => [],
            'owners' => []
        ];

        $permissionsManager = PermissionManager::getInstance();

        $perms = $permissionsManager->getPermissions();

        $ownerPerms = [];

        foreach($perms as $perm) {
            $defaultValue = $perm->getDefault();
            if ($defaultValue === Permission::DEFAULT_OP)
                $ownerPerms[] = $perm->getName();
        }

        $ownerPerms[] = self::PERMISSION_PLACE_BREAK;

        $array['owners'] = $ownerPerms;

        $this->config = new Config($this->configPath, Config::YAML, $array);

        $this->config->save();
    }

    public function updatePermissions(PracticePlayer $p) : void {

        $permissions = [];

        $name = $p->getPlayerName();

        $playerHandler = PracticeCore::getPlayerHandler();

        if($playerHandler->isMod($name))
            $permissions = $this->getModPermissions();
        elseif ($playerHandler->isAdmin($name))
            $permissions = $this->getAdminPermissions();
        elseif ($playerHandler->isContentCreator($name))
            $permissions = $this->getCCPermissions();
        elseif ($playerHandler->isOwner($name))
            $permissions = $this->getOwnerPermissions();
        elseif ($playerHandler->isBuilder($name))
            $permissions = $this->getBuilderPermissions();

        $size = count($permissions);

        $player = $p->getPlayer();

        if($size > 0 and $p->isOnline()) {

            foreach($permissions as $perm) {

                if($this->isPermission($perm)) {

                    $permission = $this->getPermission($perm);

                    $player->addAttachment(PracticeCore::getInstance(), $permission->getName(), true);
                }
            }

            $effectivePermissions = $player->getEffectivePermissions();

            $keys = array_keys($effectivePermissions);

            foreach($keys as $key) {

                $perm = $effectivePermissions[$key];

                $permName = $perm->getPermission();

                $attachment = $perm->getAttachment();

                if(!PracticeUtil::arr_contains_value($permName, $permissions) and !is_null($attachment))
                    $player->removeAttachment($attachment);
            }
        }
    }

    private function isPermission(string $name) : bool {
        return !is_null($this->getPermission($name));
    }

    private function getPermission(string $name) {
        $permManager = PermissionManager::getInstance();
        $result = $permManager->getPermission($name);
        return $result;
    }

    public function testPermission(string $permission, Player $player) : bool {

        $result = false;

        $playerHandler = PracticeCore::getPlayerHandler();

        if($playerHandler->isAdmin($player)) {
            $adminPerms = $this->getAdminPermissions();
            $result = PracticeUtil::arr_contains_value($permission, $adminPerms);
        } elseif ($playerHandler->isMod($player)) {
            $modPerms = $this->getModPermissions();
            $result = PracticeUtil::arr_contains_value($permission, $modPerms);
        } elseif ($playerHandler->isContentCreator($player)) {
            $ccPerms = $this->getCCPermissions();
            $result = PracticeUtil::arr_contains_value($permission, $ccPerms);
        } elseif ($playerHandler->isOwner($player)) {
            $ownerPerms = $this->getOwnerPermissions();
            $result = PracticeUtil::arr_contains_value($permission, $ownerPerms);
        } elseif ($playerHandler->isBuilder($player)) {
            $builderPerms = $this->getBuilderPermissions();
            $result = PracticeUtil::arr_contains_value($permission, $builderPerms);
        }

        if($result === false) $result = $player->hasPermission($permission);

        return $result;
    }

    /**
     * @return string[]
     */
    public function getBuilderPermissions() : array {
        $permissions = $this->config->get('builders');
        return $permissions;
    }

    /**
     * @return string[]
     */
    public function getCCPermissions() : array {
        $permissions = $this->config->get('content-creators');
        return $permissions;
    }

    /**
     * @return string[]
     */
    public function getModPermissions() : array {
        $ccPerms = $this->getCCPermissions();
        $modPerms = $this->config->get('mods');
        $builderPerms = $this->getBuilderPermissions();
        $result = array_merge($ccPerms, $modPerms);
        $result = array_merge($result, $builderPerms);
        return $result;
    }

    /**
     * @return string[]
     */
    public function getAdminPermissions() : array {
        $modPerms = $this->getModPermissions();
        $adminPerms = $this->config->get('admins');
        $result = array_merge($modPerms, $adminPerms);
        return $result;
    }

    /**
     * @return string[]
     */
    public function getOwnerPermissions() : array {
        $adminPerms = $this->getAdminPermissions();
        $ownerPerms = $this->config->get('owners');
        $result = array_merge($adminPerms, $ownerPerms);
        return $result;
    }


}