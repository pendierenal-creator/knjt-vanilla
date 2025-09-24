<?php

declare(strict_types=1);

namespace HorseVanilla;

use HorseVanilla\command\SpawnHorseCommand;
use HorseVanilla\entity\Horse;
use HorseVanilla\event\EventListener;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;
use pocketmine\world\World;

final class Main extends PluginBase{

    protected function onEnable() : void{
        $this->registerEntities();
        $this->registerCommands();
        $this->registerEvents();
        $this->getLogger()->info(TF::GREEN . "HorseVanilla enabled. Use /horse to spawn a horse.");
    }

    protected function onDisable() : void{
        $this->getLogger()->info(TF::YELLOW . "HorseVanilla disabled.");
    }

    private function registerEntities() : void{
        EntityFactory::getInstance()->register(Horse::class, function(World $world, CompoundTag $nbt) : Horse{
            return new Horse(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["Horse", "minecraft:horse"]);
    }

    private function registerCommands() : void{
        $this->getServer()->getCommandMap()->register($this->getName(), new SpawnHorseCommand());
    }

    private function registerEvents() : void{
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }
}
