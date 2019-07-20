<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-04-23
 * Time: 09:28
 */

declare(strict_types=1);

namespace practice\game\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityCombustByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\math\RayTraceResult;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\Player;
use practice\PracticeCore;

class FishingHook extends Projectile
{

    public const NETWORK_ID = self::FISHING_HOOK;
    public const WAIT_CHANCE = 120;
    public const CHANCE = 40;

    public $chance = false;
    public $waitChance = 240;
    public $attracted = false;
    public $attractTimer = 0;
    public $caught = false;
    public $caughtTimer = 0;
    public $fish = null;
    public $rod = null;
    public $width = 0.2;
    public $height = 0.2;
    public $gravity = 0.08;
    public $drag = 0.05;

    /* @var Entity|null */
    private $attachedEntity = null;

    public function onUpdate(int $currentTick): bool
    {
        if ($this->isFlaggedForDespawn() or !$this->isAlive()) {
            return false;
        }

        $this->timings->startTiming();

        $update = parent::onUpdate($currentTick);

        if (!$this->isCollidedVertically) {
            $this->motion->x *= 1.13;
            $this->motion->z *= 1.13;
            $this->motion->y -= $this->gravity * -0.04;
            if($this->isUnderwater()) {

                $this->motion->z = 0;
                $this->motion->x = 0;
                $difference = floatval($this->getWaterHeight() - $this->y);

                if($difference > 0.15) $this->motion->y += 0.1;
                else $this->motion->y += 0.01;
            }
            $update = true;
        } elseif ($this->isCollided and $this->keepMovement === true) {
            $this->motion->x = 0;
            $this->motion->y = 0;
            $this->motion->z = 0;
            $this->keepMovement = false;
            $update = true;
        }

        if($this->isOnGround()) $this->motion->y = 0;

        /*if($this->isUnderwater()) {
            if(!$this->attracted) {
                if($this->waitChance > 0) {
                    $this->waitChance--;
                }

                if($this->waitChance === 0) {
                    $rand = rand(0, 100);
                    if($rand < 90) {
                        $this->attractTimer = rand(0, 40) + 20;
                        $this->spawnFish();
                        $this->caught = false;
                        $this->attracted = true;
                    } else $this->waitChance = self::WAIT_CHANCE;
                }
            } elseif (!$this->caught) {
                if($this->attractFish()) {
                    $this->caughtTimer = rand(0, 20) + 30;
                    $this->fishBites();
                    $this->caught = true;
                }
            } else {

                if($this->caughtTimer > 0) {
                    $this->caughtTimer--;
                }

                if($this->caughtTimer === 0) {
                    $this->attracted = false;
                    $this->caught = false;
                    $this->waitChance = self::WAIT_CHANCE * 3;
                }
            }
        }*/

        if($this->attachedEntity !== null) {

            $pos = $this->attachedEntity->asPosition();

            if($pos !== $this->getPosition()) {
                $this->setPosition($pos->add(0, 1));
            }

            $this->setMotion($this->attachedEntity->getMotion());
        }

        $source = $this->getOwningEntity();

        if(!is_null($source) and $source instanceof Player) {

            $p = $source->getPlayer();
            $inv = $p->getInventory();
            $itemInHand = $inv->getItemInHand();

            $kill = false;

            if($source->distance($this) > 35)
                $kill = true;
            elseif ($itemInHand->getId() !== Item::FISHING_ROD)
                $kill = true;

            if($kill === true) {

                $this->kill();
                $this->close();

                $playerHandler = PracticeCore::getPlayerHandler();

                if($playerHandler->isPlayerOnline($p)) {
                    $pracPlayer = $playerHandler->getPlayer($p);
                    if($pracPlayer->isFishing()) $pracPlayer->stopFishing();
                }
            }
        }

        $this->timings->stopTiming();

        return $update;
    }
    
    public function getWaterHeight() : int {
        $floorY = $this->getFloorY();
        $result = $floorY;
        for($y = $floorY; $y < 256; $y++) {
            $id = $this->getLevel()->getBlockIdAt($this->getFloorX(), $y, $this->getFloorZ());
            if($id === 0) {
                $result = $y;
                break;
            }
        }
        return $result;
    }

    /*public function fishBites() : void {
        $this->broadcastEntityEvent(EntityEventPacket::FISH_HOOK_HOOK, 0, $this->getLevel()->getPlayers());
        $this->broadcastEntityEvent(EntityEventPacket::FISH_HOOK_BUBBLE, 0, $this->getLevel()->getPlayers());
        $this->broadcastEntityEvent(EntityEventPacket::FISH_HOOK_TEASE, 0, $this->getLevel()->getPlayers());
        $rand = new Random();
        for($i = 0; $i < 5; $i++) {
            $this->getLevel()->addParticle(new BubbleParticle(new Vector3($this->x + $rand->nextFloat() * 0.5 - 0.25,  $this->getWaterHeight(), $this->z + $rand->nextFloat() * 0.5 - 0.25)));
        }
    }

    public function spawnFish() : void {
        $rand = new Random();
        $this->fish = new Vector3($this->x + ($rand->nextFloat() * 1.2 + 1) * ($rand->nextBoolean() ? -1 : 1), $this->getWaterHeight(), $this->z + ($rand->nextFloat() * 1.2 + 1) * ($rand->nextBoolean() ? -1 : 1));
    }

    public function attractFish() : bool {
        $multiply = 0.1;
        $result = false;
        if($this->fish instanceof Vector3) {
            $this->fish->setComponents($this->fish->x + ($this->x - $this->fish->x) * $multiply, $this->fish->y, $this->fish->z + ($this->z - $this->fish->z) * $multiply);
            $rand = rand(0, 100);
            if($rand < 85) {
                $this->getLevel()->addParticle(new WaterParticle($this->fish));
            }
            $dist = abs(sqrt($this->x * $this->x + $this->z * $this->z) - sqrt($this->fish->x * $this->fish->x - $this->fish->z * $this->fish->z));
            $result = $dist < 0.15;
        }
        return $result;
    }*/

    public function reelLine() : void {

        $e = $this->getOwningEntity();

        if($e instanceof Player and $this->caught === true) {
            $this->broadcastEntityEvent(ActorEventPacket::FISH_HOOK_TEASE, 0, $this->getLevel()->getPlayers());
        }

        if(!$this->closed) {
            $this->kill();
            $this->close();
        }
    }

    public function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void
    {
        $damage = $this->getResultDamage();

        $this->attachedEntity = $entityHit;

        if($damage >= 0){

            if($this->getOwningEntity() === null){
                $ev = new EntityDamageByEntityEvent($this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
            }else{
                $ev = new EntityDamageByChildEntityEvent($this->getOwningEntity(), $this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
            }

            $entityHit->attack($ev);

            if($this->isOnFire()){
                $ev = new EntityCombustByEntityEvent($this, $entityHit, 5);
                $ev->call();
                if(!$ev->isCancelled()){
                    $entityHit->setOnFire($ev->getDuration());
                }
            }
        }
    }

    public function getResultDamage(): int
    {
        return parent::getResultDamage();
    }
}