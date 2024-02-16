<?php

declare(strict_types=1);

namespace nhiwentwest\KillEntity;

use pmmp\TesterPlugin\TestFailedException;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Utils;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\BedrockEconomy;
use cooldogedev\BedrockEconomy\libs\cooldogedev\libSQL\context\ClosureContext;
use InvalidArgumentException;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\permission\DefaultPermissions;
use nhiwentwest\KillEntity\economy\EconomyIntegration;
use nhiwentwest\KillEntity\economy\EconomyManager;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\math\Vector3;
use pocketmine\entity\Zombie;
use pocketmine\entity\Location;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class Main extends PluginBase implements Listener {
    
    public $myConfig;
    public static $instance; 

	
    public function onEnable(): void {
        self::$instance = $this;
        EconomyManager::init($this);
   
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder());
        $this->saveResource("config.yml");
        $this->myConfig = new Config($this->getDataFolder() . "config.yml", Config::YAML);
     
      
        }


public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
		
	if ($sender instanceof Player and !$sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
			$sender->sendMessage("§cYou do not have permission to use this commands§r");
			return true;
		}
	
if ($label === "zombie") {
    $x1 = $this->getConfig()->get("x1");
    $y1 = $this->getConfig()->get("y1");
    $z1 = $this->getConfig()->get("z1");
    
    $x1 = (float) $x1;
    $y1 = (float) $y1;
    $z1 = (float) $z1;
   
    $x2 = $this->getConfig()->get("x2");
    $y2 = $this->getConfig()->get("y2");
    $z2 = $this->getConfig()->get("z2");
    
    $x2 = (float) $x2;
    $y2 = (float) $y2;
    $z2 = (float) $z2;

   $yaw = 0.0; // Đặt yaw thành 0
   $pitch = 0.0; // Đặt pitch thành 0
	
$x = mt_rand(min($x1, $x2), max($x1, $x2));
$y = mt_rand(min($y1, $y2), max($y1, $y2));
$z = mt_rand(min($z1, $z2), max($z1, $z2));

    $x = (float) $x;
    $y = (float) $y;
    $z = (float) $z;
   

   
    // Lấy thế giới mặc định
	
$worldName = "world"; // Thay "your_world_name" bằng tên thế giới của bạn
$worldManager = Server::getInstance()->getWorldManager();
$world = $worldManager->getWorldByName($worldName);
   $pos = new Vector3($x, $y, $z);


    $location = new Location($x, $y, $z, $world, $yaw, $pitch);
	
    $zombie = new Zombie($location);

	

    // Gửi đối tượng Zombie tới tất cả người chơi trong thế giới
    $zombie->spawnToAll();
	  $this->getLogger()->info("Zombie had been summon.");
return true;
}
	return true;
	}
	

 
        public function onEntityDeath(EntityDeathEvent $event): void {
            
    
        $killedEntity = $event->getEntity();
        $cause = $killedEntity->getLastDamageCause();
   
        if ($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();
   
         
            if ($damager instanceof Player) {
                if ($damager->hasPermission("killentity.plugin")) {
                $levelName = $damager->getWorld()->getFolderName();
                
                      
                if(in_array($levelName, $this->getConfig()->get("worlds"))){
                $allowedEntityTypes = $this->getConfig()->get("animals");
	
             
                foreach ($allowedEntityTypes as $index => $entityData) {
                    $entityType = key($entityData);
                    $moneyReward = current($entityData);
            
              
                    
                    
                    if ($killedEntity->getName() === $entityType) {
                     
                       
                            $playerName = $damager->getName();
                      
                        $economy = EconomyManager::get();
                        $economy->addMoney($damager, $moneyReward);
                        
                        
                        $msg = $this->getConfig()->get("message");
                              

                               if ($msg === 1) {
                                
                                   $customMessage = TextFormat::GREEN . "+" . TextFormat::YELLOW . "$" . $moneyReward;
                                   $damager->sendPopup($customMessage);
                               } elseif ($msg === 2) {
                                  
                                   $customMessage = TextFormat::GREEN . "+" . TextFormat::YELLOW . "$" . $moneyReward;
                                   $damager->sendMessage($customMessage);
                                   
                               }
                               
                        elseif ($msg === 3) {
                            continue;
                        }
                               else {
                                   // Default case: 'message' is not set or has an invalid value
                                   $this->getLogger()->info("Invalid value for 'message' in the config.");
                               }

                          
                        }
                        }
                    }

			    else {
Server::getInstance()->getLogger()->info("KillEntity: The world is not activated");
		    
	    }

			
                }

		        else {
 $damager->sendMessage("You don't have permission to earn coin.");
		    
	    }
		    
            }
        }
    }
    
    
    public function onPlayerDeath(PlayerDeathEvent $event): void {
          $player = $event->getPlayer();
          $cause = $player->getLastDamageCause();
        
        $playerName = $player->getName();
        
     

        if ($player->hasPermission("killentity.plugin")) {
            
        if ($cause instanceof EntityDamageByEntityEvent) {
            
            $damager = $cause->getDamager();
           
           
            
        
            $lostPercentage = $this->getConfig()->get("percent");
            
        
            $levelName = $player->getWorld()->getFolderName();
            
                  
            if(in_array($levelName, $this->getConfig()->get("worlds"))){
            $allowedEntityTypes = $this->getConfig()->get("animals");
         
            foreach ($allowedEntityTypes as $index => $entityData) {
                $entityType = key($entityData);
 
                  $economy = EconomyManager::get();
                    
                    $msg = $this->getConfig()->get("message");
                          
              $economy->getMoney($player, static function(float $money) use($player, $lostPercentage, $msg) : void {
                               
                                 $currentBalance = $money;

                                 $amountToDeduct = (int) ceil($currentBalance * ($lostPercentage / 100));
                                 
                                 $economy = EconomyManager::get();
                                 $economy->removeMoney($player, $amountToDeduct);
                                 
                                 
                                 if ($msg === 2) {
                                  
                                     $customMessage = TextFormat::RED . "-" . TextFormat::YELLOW . "$" . $amountToDeduct;
                                 $player->sendMessage($customMessage);
                                 }
                                 
                                 
              });
 
                }
                }
	    else {
Server::getInstance()->getLogger()->info("KillEntity: The world is not activated");
		    
	    }
                }
            
              
            
            }
        
                   else {
 $player->sendMessage("You don't have permission to earn coin.");
		    
	    }

    
    }





    
   }



