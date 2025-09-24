<?php

declare(strict_types=1);

namespace HorseVanilla\command;

use HorseVanilla\entity\Horse;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;

final class SpawnHorseCommand extends Command{

    public function __construct(){
        parent::__construct("horse", "Spawn a tame vanilla horse", "/horse [name]", []);
        $this->setPermission("horsevanilla.command.spawn");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
        if(!$this->testPermission($sender)){
            return true;
        }

        if(!$sender instanceof Player){
            $sender->sendMessage(TF::RED . "Only players can use this command.");
            return true;
        }

        $location = $sender->getLocation();
        $spawnLocation = new Location(
            $location->getX(),
            $location->getY(),
            $location->getZ(),
            $location->getWorld(),
            $location->getYaw(),
            $location->getPitch()
        );

        $horse = new Horse($spawnLocation);
        if(isset($args[0])){
            $name = trim(implode(" ", $args));
            if($name !== ''){
                $horse->setNameTag($name);
                $horse->setNameTagVisible(true);
                $horse->setNameTagAlwaysVisible(true);
            }
        }

        $horse->spawnToAll();

        $sender->sendMessage(TF::GREEN . "Horse spawned at your location.");
        return true;
    }
}
