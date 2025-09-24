<?php

declare(strict_types=1);

namespace HorseVanilla\event;

use HorseVanilla\entity\Horse;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEntityEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;

use function max;
use function strcasecmp;

final class EventListener implements Listener{

    public function onPlayerInteractEntity(PlayerInteractEntityEvent $event) : void{
        $entity = $event->getEntity();
        if(!$entity instanceof Horse){
            return;
        }

        $player = $event->getPlayer();
        $item = $event->getItem();

        if($this->handleBreedingFeed($player, $entity, $item)){
            $event->cancel();
            return;
        }

        if(!$item->isNull()){
            // Require an empty hand for mounting/taming attempts when not feeding.
            return;
        }

        if($entity->isTamed()){
            $owner = $entity->getOwnerName();
            if($owner !== null && strcasecmp($owner, $player->getName()) !== 0){
                $player->sendMessage(TF::RED . "This horse belongs to {$owner}.");
                $event->cancel();
                return;
            }

            $player->sendMessage(TF::GREEN . "The horse is already tame.");
            return;
        }

        if($entity->attemptTaming($player)){
            $player->sendMessage(TF::GOLD . "Hearts appear! The horse accepts you as its rider.");
        }else{
            $player->sendMessage(TF::YELLOW . "The horse bucks you off. Temper: " . $entity->getTemper() . "/100");
        }

        $event->cancel();
    }

    public function onEntityDeath(EntityDeathEvent $event) : void{
        $entity = $event->getEntity();
        if(!$entity instanceof Horse){
            return;
        }

        if(!$entity->isTamed()){
            return;
        }

        $name = $entity->getNameTag() !== '' ? $entity->getNameTag() : 'A tame horse';
        $message = TF::RED . $name . " has died.";
        foreach($entity->getWorld()->getPlayers() as $player){
            $player->sendMessage($message);
        }
    }

    private function handleBreedingFeed(Player $player, Horse $horse, Item $item) : bool{
        if($item->isNull()){
            return false;
        }

        $isGoldenApple = $item->equals(VanillaItems::GOLDEN_APPLE(), true, true);
        $isGoldenCarrot = $item->equals(VanillaItems::GOLDEN_CARROT(), true, true);

        if(!$isGoldenApple && !$isGoldenCarrot){
            return false;
        }

        if(!$horse->isTamed()){
            $horse->increaseTemper(5);
            $player->sendMessage(TF::YELLOW . "The horse nibbles the treat. Temper: " . $horse->getTemper() . "/100");
            $this->consumeItem($player, $item);
            return true;
        }

        if($horse->isBaby()){
            if($horse->accelerateGrowth()){
                $player->sendMessage(TF::GREEN . "The foal grows a little larger.");
                $this->consumeItem($player, $item);
            }
            return true;
        }

        if(!$horse->setInLove($player)){
            $player->sendMessage(TF::RED . "The horse cannot breed right now.");
            return true;
        }

        $player->sendMessage(TF::LIGHT_PURPLE . "Hearts surround the horse! Find another in love horse nearby.");
        $this->consumeItem($player, $item);

        foreach($horse->getWorld()->getNearbyEntities($horse->getBoundingBox()->expandedCopy(4.0, 2.0, 4.0)) as $nearby){
            if(!$nearby instanceof Horse || $nearby === $horse){
                continue;
            }

            $child = $horse->breedWith($nearby);
            if($child === null){
                continue;
            }

            $child->spawnToAll();
            $message = TF::GREEN . "A baby horse has been born!";
            $horse->getWorld()->broadcastMessage($message);
            break;
        }

        return true;
    }

    private function consumeItem(Player $player, Item $item) : void{
        if($player->isCreative()){ // creative players don't consume items
            return;
        }

        $inventory = $player->getInventory();
        $inHand = $inventory->getItemInHand();
        if(!$inHand->equals($item, true, true)){
            return;
        }

        if($inHand->getCount() <= 1){
            $inventory->setItemInHand(VanillaItems::AIR());
            return;
        }

        $inHand->setCount(max(1, $inHand->getCount() - 1));
        $inventory->setItemInHand($inHand);
    }
}
