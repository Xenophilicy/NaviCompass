<?php
# MADE BY:
#  __    __                                          __        __  __  __                     
# /  |  /  |                                        /  |      /  |/  |/  |                    
# $$ |  $$ |  ______   _______    ______    ______  $$ |____  $$/ $$ |$$/   _______  __    __ 
# $$  \/$$/  /      \ /       \  /      \  /      \ $$      \ /  |$$ |/  | /       |/  |  /  |
#  $$  $$<  /$$$$$$  |$$$$$$$  |/$$$$$$  |/$$$$$$  |$$$$$$$  |$$ |$$ |$$ |/$$$$$$$/ $$ |  $$ |
#   $$$$  \ $$    $$ |$$ |  $$ |$$ |  $$ |$$ |  $$ |$$ |  $$ |$$ |$$ |$$ |$$ |      $$ |  $$ |
#  $$ /$$  |$$$$$$$$/ $$ |  $$ |$$ \__$$ |$$ |__$$ |$$ |  $$ |$$ |$$ |$$ |$$ \_____ $$ \__$$ |
# $$ |  $$ |$$       |$$ |  $$ |$$    $$/ $$    $$/ $$ |  $$ |$$ |$$ |$$ |$$       |$$    $$ |
# $$/   $$/  $$$$$$$/ $$/   $$/  $$$$$$/  $$$$$$$/  $$/   $$/ $$/ $$/ $$/  $$$$$$$/  $$$$$$$ |
#                                         $$ |                                      /  \__$$ |
#                                         $$ |                                      $$    $$/ 
#                                         $$/                                        $$$$$$/

namespace Xenophilicy\NaviCompass;

use pocketmine\event\player\{PlayerJoinEvent,PlayerInteractEvent,PlayerQuitEvent};
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\command\{Command,PluginCommand,CommandSender,ConsoleCommandSender};
use pocketmine\item\enchantment\{Enchantment,EnchantmentInstance};
use pocketmine\item\Item;
use pocketmine\inventory\transaction\action\{SlotChangeAction,DropItemAction};
use pocketmine\plugin\PluginBase;
use pocketmine\utils\{Config,TextFormat as TF};
use pocketmine\Player;

use Xenophilicy\NaviCompass\libs\jojoe77777\FormAPI\SimpleForm;
use Xenophilicy\NaviCompass\Task\QueryTaskCaller;

class NaviCompass extends PluginBase implements Listener{

    private static $plugin;

    public function onEnable(){
        self::$plugin = $this;
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $configPath = $this->getDataFolder()."config.yml";
        if(!file_exists($configPath)){
            $this->getLogger()->critical("It appears that this is the first time you are using NaviCompass! This plugin does not function with the default config.yml, so please edit it to your preferred settings before attempting to use it.");
            $this->saveDefaultConfig();
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        $this->saveDefaultConfig();
        $this->config = new Config($configPath, Config::YAML);
        $this->config->getAll();
        $version = $this->config->get("VERSION");
        $this->pluginVersion = $this->getDescription()->getVersion();
        if($version < "2.1.0"){
            $this->getLogger()->warning("You have updated NaviCompass to v".$this->pluginVersion." but have a config from v$version! Please delete your old config for new features to be enabled and to prevent unwanted errors! Plugin will remain disabled...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
        if($this->config->getNested("Selector.Enabled")){
            $this->selectorSupport = true;
            $this->createEnchant();
            $this->selectorName = TF::ITALIC.(str_replace("&", "§", $this->config->getNested("Selector.Name")));
            $this->selectorLore = [str_replace("&", "§", $this->config->getNested("Selector.Lore"))];
            $this->itemType = $this->config->getNested("Selector.Item");
            $this->forceSlot = $this->config->getNested("Selector.Force-Slot");
        } else{
            $this->selectorSupport = false;
            $this->getLogger()->info("Selector item disabled in config...");
        }
        if($this->config->getNested("Command.Enabled")){
            $this->commandSupport = true;
            $this->cmdName = str_replace("/","",$this->config->getNested("Command.Name"));
            if($this->cmdName == null || $this->cmdName == ""){
                $this->getLogger()->critical("Invalid UI command string found, disabling plugin...");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
            } else{
                $cmd = new PluginCommand($this->cmdName , $this);
                $cmd->setDescription($this->config->getNested("Command.Description"));
                if($this->config->getNested("Command.Permission.Enabled")){
                    $cmd->setPermission($this->config->getNested("Command.Permission.Node"));
                }
                $this->getServer()->getCommandMap()->register("NaviCompass", $cmd, $this->cmdName);
            }
        } else{
            $this->commandSupport = false;
            $this->getLogger()->info("Command method disabled in config...");
        }
        $transferType = $this->config->get("Transfer-Type");
        switch(strtolower($transferType)){
            case "external":
                $this->extLimit = false;
                break;
            case "hybrid":
            case "internal":
                if($this->config->get("World-CMD") == null || $this->config->get("World-CMD") == ""){
                    $this->getLogger()->critical("Null world command string found, limiting to external use!");
                    $this->extLimit = true;
                } else{
                    $mode = $this->config->get("World-CMD-Mode");
                    if(strtolower($mode) == "player"){
                        $this->extLimit = false;
                        $this->cmdMode = 0;
                    } elseif(strtolower($mode) == "console"){
                        $this->extLimit = false;
                        $this->cmdMode = 1;
                    } else{
                        $this->getLogger()->critical("Null world command mode found, limiting to external use!");
                        $this->extLimit = true;
                    }
                }
                break;
            case false:
            case null:
                $this->getLogger()->critical("Null transfer type found, disabling plugin!");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
            default:
                $this->getLogger()->critical("Invalid transfer type! Input type: ".$transferType." not supported, disabling plugin!");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
        }
        $levels = scandir($this->getServer()->getDataPath()."worlds/");
        foreach($levels as $level){
            if($level === "." || $level === ".."){
                continue;
            }
            $this->getServer()->loadLevel($level);
        }
        $this->list = [];
        foreach($this->config->get("List") as $target){
            unset($search);
            $value = explode(":", $target);
            if(strtolower($value[0]) === "ext"){
                if(isset($value[4])){
                    $search = $value[4];
                }
                $this->queryResults[$value[2].":".$value[3]] = [];
                $this->startQueryTask($value[2],$value[3]);
                array_push($this->list, $target);
            } elseif(strtolower($value[0]) === "int" && !$this->extLimit){
                $level = $this->getServer()->getLevelByName($value[2]);
                if($level === null){
                    if($value[2] == "xenoCreative"){
                        $this->getLogger()->critical("You are using a default server/world configuration! Please change this to YOUR servers/worlds for the plugin to function properly! Plugin will remain disabled until default config is changed...");
                        $this->getServer()->getPluginManager()->disablePlugin($this);
                        return;
                    } else{
                        $this->getLogger()->critical("Invalid world name! Name: ".$value[2]." was not found, disabling plugin! Be sure you use the name of the world folder for the 'WorldAlias' key in the config!");
                        $this->getServer()->getPluginManager()->disablePlugin($this);
                        return;
                    }
                }
                if(isset($value[3])){
                    $search = $value[3];
                    if(!isset($value[4])){
                        $this->getLogger()->warning("Null path/URL! Input: ".$value[1]);
                    }
                }
                array_push($this->list, $target);
            }
            if(isset($search)){
                switch(strtolower($search)){
                    case'url':
                        break;
                    case'path':
                        break;
                    default:
                        $this->getLogger()->warning("Invalid image type! Input: ".$value[1].TF::RESET.TF::YELLOW." Image type: ".$search.TF::RESET.TF::YELLOW." not supported. ");
                }
            }
        }
    }

    private function isSelectorItem(Item $item) : bool{
        if($this->selectorSupport){
            if($item->getCustomName() == $this->selectorName && $item->getId() == $this->itemType && $item->getLore() == $this->selectorLore){
                return true;
            }
        }
        return false;
    }

    private function createEnchant(){
        Enchantment::registerEnchantment(new Enchantment(100, "", 0, 0, 0, 1));
        $enchantment = Enchantment::getEnchantment(100);
        $this->enchInst = new EnchantmentInstance($enchantment, 1);
    }

    private function startQueryTask(string $host, int $port){
        $this->getScheduler()->scheduleRepeatingTask(new QueryTaskCaller($this, $host, $port), 100);
    }

    public function queryTaskCallback($result, string $host, int $port){
		$this->queryResults[$host.":".$port] = $result;
    }
    
    public static function getPLugin(){
        return self::$plugin;
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if ($command->getName() == "navicompass"){
            $sender->sendMessage(TF::GRAY."---".TF::GOLD." NaviCompass ".TF::GRAY."---");
            $sender->sendMessage(TF::YELLOW."Version: ".TF::AQUA.$this->pluginVersion);
            $sender->sendMessage(TF::YELLOW."Description: ".TF::AQUA."View servers or worlds");
            if($this->selectorSupport){
                $sender->sendMessage(TF::YELLOW."Selector: ".TF::GREEN."Enabled");
            } else{
                $sender->sendMessage(TF::YELLOW."Selector: ".TF::RED."Disabled");
            }
            if($this->commandSupport){
                $sender->sendMessage(TF::YELLOW."Command: ".TF::GREEN."Enabled");
                $sender->sendMessage(TF::LIGHT_PURPLE ." - ".TF::BLUE."/".$this->cmdName);
            } else{
                $sender->sendMessage(TF::YELLOW."Command: ".TF::RED."Disabled");
            }
            $sender->sendMessage(TF::GRAY."-------------------");
        }
        if($this->commandSupport && $command->getName() == $this->cmdName){
            if($sender instanceof Player){
                $this->serverList($sender);
            } else{
                $sender->sendMessage(" ".$this->config->getNested("UI.Title"));
                foreach($this->list as $target){
                    $value = explode(":", $target);
                    $value = str_replace("&", "§", $value);
                    if(strtolower($value[0]) == "ext"){
                        $sender->sendMessage(TF::YELLOW."Name: ".$value[1].TF::RESET.TF::YELLOW." | IP: ".$value[2]." | Port: ".$value[3]);
                    } else{
                        $sender->sendMessage(TF::YELLOW."Name: ".$value[1].TF::RESET.TF::YELLOW." | Alias: ".$value[2]);
                    }
                }
            }
        }
        return true;
    }

    public function serverList($player){
        $form = new SimpleForm(function (Player $player, $data){
            if($data === null){
                return;
            } else{
                $value = explode(":", $this->list[$data]);
                $value = str_replace("&", "§", $value);
                if(strtolower($value[0]) == "ext"){
                    $player->transfer($value[2],$value[3]);
                } else{
                    $cmdStr = $this->config->get("World-CMD");
                    $cmdStr = str_replace("{player}", $player->getName(), $cmdStr);
                    $cmdStr = str_replace("{world}", $value[2], $cmdStr);
                    if($this->cmdMode == 0){
                        $this->getServer()->getCommandMap()->dispatch($player, $cmdStr);
                    } else{
                        $this->getServer()->getCommandMap()->dispatch(new ConsoleCommandSender(), $cmdStr);
                    }
                }
            }
            return true;
        });
        $form->setTitle($this->config->getNested("UI.Title"));
        $form->setContent($this->config->getNested("UI.Message"));
        foreach($this->list as $target){
            $value = explode(":", $target);
            $value = str_replace("&", "§", $value);
            unset($search);
            if(strtolower($value[0]) == "ext"){
                $subtext = $this->config->getNested("UI.Server-Button-Subtext");
                $queryResult = $this->queryResults[$value[2].":".$value[3]];
                if($queryResult[0] === "online"){
                    $subtext = str_replace("{status}", TF::GREEN."Online".TF::RESET, $subtext);
                    $subtext = str_replace("{current-players}", $queryResult[1], $subtext);
                    $subtext = str_replace("{max-players}", $queryResult[2], $subtext);
                } else{
                    $subtext = str_replace("{status}", TF::RED."Offline".TF::RESET, $subtext);
                    $subtext = str_replace("{current-players}", "-", $subtext);
                    $subtext = str_replace("{max-players}", "-", $subtext);
                }
                if(isset($value[4])){
                    $search = $value[4];
                    $file = $value[5];
                }
            } else{
                $subtext = $this->config->getNested("UI.World-Button-Subtext");
                $worldPlayerCount = sizeof($this->getServer()->getLevelByName($value[2])->getPlayers());
                $subtext = str_replace("{current-players}", $worldPlayerCount, $subtext);
                if(isset($value[3])){
                    $search = $value[3];
                    $file = $value[4];
                }
            }
            if(isset($search)){
                if($search == "url"){
                    $form->addButton($value[1]."\n".$subtext, 1, "http://".$file);
                }
                if($search == "path"){
                    $form->addButton($value[1]."\n".$subtext, 0, $file);
                }
            } else{
                $form->addButton($value[1]."\n".$subtext);
            }
        }
        $form->sendToPlayer($player);
    }

    public function onJoin(PlayerJoinEvent $event){
        if($this->selectorSupport){
            $player = $event->getPlayer();
            $item = Item::get($this->itemType);
            $item->setCustomName($this->selectorName);
            $item->setLore($this->selectorLore);
            $item->addEnchantment($this->enchInst);
            $slot = $this->config->getNested("Selector.Slot");
            $player->getInventory()->setItem($slot,$item,true);
        }
    }

    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        $items = $player->getInventory()->getContents();
        foreach($items as $target){
            if($this->isSelectorItem($target)){
                $player->getInventory()->remove($target);
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event){
        if($this->selectorSupport){
            $player = $event->getPlayer();
            $item = $player->getInventory()->getItemInHand();
            if($this->isSelectorItem($item)){
                $this->serverList($player);
            }
        }
    }

    public function onInventoryTransaction(InventoryTransactionEvent $event){
        if($this->selectorSupport && $this->forceSlot){
            $transaction = $event->getTransaction();
            foreach($transaction->getActions() as $action){
                $item = $action->getSourceItem();
                $source = $transaction->getSource();
                if($source instanceof Player && $this->isSelectorItem($item)){
                    if($action instanceof SlotChangeAction || $action instanceof DropItemAction){
                        $event->setCancelled();
                    }
                }
            }
        }
    }
}
