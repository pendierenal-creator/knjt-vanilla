<?php

declare(strict_types=1);

namespace HorseVanilla\entity;

use pocketmine\entity\animal\Animal;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use pocketmine\world\particle\HeartParticle;

use function max;
use function min;
use function mt_rand;

class Horse extends Animal{

    private const BASE_MAX_HEALTH = 30;
    private const TEMPER_INCREASE_STEP = 5;
    private const LOVE_MODE_TICKS = 20 * 30; // 30 seconds
    private const BABY_GROW_TICKS = 20 * 60 * 5; // 5 minutes to fully grow

    private bool $tamed = false;
    private ?string $ownerName = null;
    private int $temper = 0;
    private ?int $tamingThreshold = null;
    private int $loveTicks = 0;
    private bool $isBaby = false;
    private int $growthTicks = 0;

    public function __construct(Location $location, ?CompoundTag $nbt = null){
        parent::__construct($location, $nbt);
        if(!$this->hasCustomName()){
            $this->setNameTag(TF::GOLD . "Horse");
        }
        $this->setNameTagVisible(true);
    }

    public static function getNetworkTypeId() : string{
        return EntityIds::HORSE;
    }

    protected function getInitialSizeInfo() : EntitySizeInfo{
        return new EntitySizeInfo(1.6, 1.3965, 1.4);
    }

    protected function initEntity(CompoundTag $nbt) : void{
        parent::initEntity($nbt);
        $this->setMaxHealth(self::BASE_MAX_HEALTH);
        $this->setHealth(self::BASE_MAX_HEALTH);

        $this->tamed = $nbt->getByte("Tamed", 0) === 1;
        $owner = $nbt->getString("Owner", "");
        $this->ownerName = $owner !== '' ? $owner : null;
        $this->temper = $nbt->getInt("Temper", 0);
        $threshold = $nbt->getInt("TamingThreshold", -1);
        $this->tamingThreshold = $threshold >= 0 ? $threshold : null;
        $this->loveTicks = $nbt->getInt("LoveTicks", 0);
        $this->isBaby = $nbt->getByte("Baby", 0) === 1;
        $this->growthTicks = $nbt->getInt("GrowthTicks", 0);

        if($this->isBaby){
            $this->setScale(0.5);
        }
    }

    protected function saveNBT() : CompoundTag{
        $nbt = parent::saveNBT();
        $nbt->setByte("Tamed", $this->tamed ? 1 : 0);
        if($this->ownerName !== null){
            $nbt->setString("Owner", $this->ownerName);
        }
        $nbt->setInt("Temper", $this->temper);
        $nbt->setInt("TamingThreshold", $this->tamingThreshold ?? -1);
        $nbt->setInt("LoveTicks", $this->loveTicks);
        $nbt->setByte("Baby", $this->isBaby ? 1 : 0);
        $nbt->setInt("GrowthTicks", $this->growthTicks);
        return $nbt;
    }

    public function entityBaseTick(int $tickDiff = 1) : bool{
        $hasUpdate = parent::entityBaseTick($tickDiff);

        if($this->loveTicks > 0){
            $this->loveTicks = max(0, $this->loveTicks - $tickDiff);
            if($this->loveTicks > 0 && $this->ticksLived % 20 === 0){
                $this->getWorld()->addParticle($this->getPosition()->add(0, 1.2, 0), new HeartParticle());
            }
            $hasUpdate = true;
        }

        if($this->isBaby && $this->growthTicks > 0){
            $this->growthTicks = max(0, $this->growthTicks - $tickDiff);
            if($this->growthTicks === 0){
                $this->setBaby(false);
            }
            $hasUpdate = true;
        }

        return $hasUpdate;
    }

    protected function getDrops() : array{
        return [];
    }

    public function getXpDropAmount() : int{
        return 1;
    }

    public function isTamed() : bool{
        return $this->tamed;
    }

    public function getOwnerName() : ?string{
        return $this->ownerName;
    }

    public function attemptTaming(Player $player) : bool{
        if($this->isTamed()){
            return true;
        }

        if($this->tamingThreshold === null){
            $this->tamingThreshold = mt_rand(0, 99);
        }

        if($this->temper > $this->tamingThreshold){
            $this->tame($player);
            return true;
        }

        $this->increaseTemper(self::TEMPER_INCREASE_STEP);

        if($this->temper > $this->tamingThreshold){
            $this->tame($player);
            return true;
        }

        return false;
    }

    public function increaseTemper(int $amount) : int{
        $this->temper = min(100, $this->temper + $amount);
        return $this->temper;
    }

    public function getTemper() : int{
        return $this->temper;
    }

    public function isInLove() : bool{
        return $this->loveTicks > 0;
    }

    public function setInLove(?Player $player = null) : bool{
        if(!$this->canBreed()){
            return false;
        }

        if($this->isInLove()){
            return false;
        }

        $this->loveTicks = self::LOVE_MODE_TICKS;
        $this->spawnHearts(8);
        return true;
    }

    public function canBreed() : bool{
        return $this->isTamed() && !$this->isBaby;
    }

    public function breedWith(Horse $partner) : ?Horse{
        if($partner === $this || !$this->canBreed() || !$partner->canBreed()){
            return null;
        }

        if(!$this->isInLove() || !$partner->isInLove()){
            return null;
        }

        $this->resetLove();
        $partner->resetLove();

        $posA = $this->getPosition();
        $posB = $partner->getPosition();
        $midpoint = (new Vector3(
            ($posA->getX() + $posB->getX()) / 2,
            ($posA->getY() + $posB->getY()) / 2,
            ($posA->getZ() + $posB->getZ()) / 2
        ));

        $childLocation = new Location($midpoint->getX(), $midpoint->getY(), $midpoint->getZ(), $this->location->getWorld(), 0.0, 0.0);
        $child = new self($childLocation);
        $child->makeBaby();
        $child->setNameTag(TF::GOLD . "Foal");

        return $child;
    }

    public function accelerateGrowth() : bool{
        if(!$this->isBaby){
            return false;
        }

        if($this->growthTicks <= 0){
            $this->setBaby(false);
            return false;
        }

        $this->growthTicks = max(0, $this->growthTicks - (self::BABY_GROW_TICKS / 5));
        if($this->growthTicks === 0){
            $this->setBaby(false);
        }

        $this->spawnHearts(4);
        return true;
    }

    public function isBaby() : bool{
        return $this->isBaby;
    }

    public function makeBaby() : void{
        $this->setBaby(true);
        $this->growthTicks = self::BABY_GROW_TICKS;
        $this->temper = 0;
        $this->tamingThreshold = null;
        $this->tamed = false;
        $this->ownerName = null;
    }

    private function setBaby(bool $baby) : void{
        $this->isBaby = $baby;
        $this->setScale($baby ? 0.5 : 1.0);
    }

    private function tame(Player $player) : void{
        $this->tamed = true;
        $this->ownerName = $player->getName();
        $this->tamingThreshold = null;
        $this->spawnHearts(8);
        if(!$this->hasCustomName()){
            $this->setNameTag(TF::GOLD . $player->getName() . "'s Horse");
        }
    }

    private function resetLove() : void{
        $this->loveTicks = 0;
    }

    private function spawnHearts(int $count = 3) : void{
        $world = $this->getWorld();
        $position = $this->getPosition();
        $particlePosition = $position->add(0.0, 1.2, 0.0);
        for($i = 0; $i < $count; ++$i){
            $world->addParticle($particlePosition, new HeartParticle());
        }
    }
}
