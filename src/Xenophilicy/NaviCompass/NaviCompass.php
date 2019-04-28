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

use pocketmine\event\player\{PlayerJoinEvent,PlayerInteractEvent,PlayerQuitEvent,PlayerDropItemEvent};
use pocketmine\command\{Command,CommandSender,ConsoleCommandSender};
use pocketmine\item\enchantment\{Enchantment,EnchantmentInstance};
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\item\Item;

use Xenophilicy\NaviCompass\libs\jojoe77777\FormAPI\SimpleForm;

class NaviCompass extends PluginBase implements Listener {

    private $config;

    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();
        $this->config = new Config($this->getDataFolder()."config.yml", Config::YAML);
        $this->config->getAll();
        $this->getLogger()->info("NaviCompass has been enabled!");
        $version = $this->config->get("VERSION");
        if($version != "1.0.3"){
            $this->getLogger()->warning("You have updated NaviCompass but have an old config! Please delete your old config for new features to be enabled!");
        }
        $selectorEnable = $this->config->get("Selector-Support");
        if ($selectorEnable == true) {
            $this->selectorSupport = true;
        }
        else{
            $this->selectorSupport = false;
            $this->getLogger()->notice("§eSelector item support turned off in config! Disabling selector...");
        }
        $transferType = $this->config->get("Transfer-Type");
        switch($transferType){
            case "External":
                $this->tranType = 0;
                break;
            case "Internal":
                if($this->config->get("World-CMD") == null || $this->config->get("World-CMD") == ""){
                    $this->getLogger()->notice("Null world command string found, plugin default to External use!");
                    $this->tranType = 0;
                }
                else{
                    if($this->config->get("World-CMD-Mode") == "Player"){
                        $this->tranType = 1;
                        $this->cmdMode = 0;
                    }
                    elseif($this->config->get("World-CMD-Mode") == "Console"){
                        $this->tranType = 1;
                        $this->cmdMode = 1;
                    }
                    else{
                        $this->getLogger()->notice("Null world command mode found, plugin default to External use!");
                        $this->tranType = 0;
                    }
                }
                break;
            case false:
            case null:
                $this->getLogger()->notice("Null transfer type found, plugin default to External use!");
                $this->tranType = 0;
                break;
            default:
                $this->getLogger()->notice("Invalid transfer type! Input type: ".$transferType." not supported, plugin default to External use!");
                $this->tranType = 0;
        }
        $this->list = $this->config->get("List");
        foreach ($this->list as $target) {
            unset($search);
            $value = explode(":", $target);
            if($this->tranType === 0){
                if(isset($value[3])){
                    $search = $value[3];
                }
            }
            else{
                $search = $value[2];
            }
            if(isset($search)){
                switch($search){
                    case'url':
                        break;
                    case'path':
                        break;
                    default:
                        $this->getLogger()->warning("Invalid image type! Input: ".$value[0]."§r§e Image type: ".$search."§r§e not supported. ");
                }
            }
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if ($command->getName() == "servers"){
            if ($sender instanceof Player) {
                $this->serverList($sender);
            }
            else{
                if($this->tranType == 0){
                    $sender->sendMessage(" ".$this->config->get("UI-Title"));
                    foreach ($this->list as $target){
                        $value = explode(":", $target);
                        $value = str_replace("&", "§", $value);
                        $sender->sendMessage("§eName: ".$value[0]."§r§e | IP: ".$value[1]." | Port: ".$value[2]);
                    }
                }
                elseif($this->tranType ==1){
                    foreach ($this->list as $target){
                        $value = explode(":", $target);
                        $value = str_replace("&", "§", $value);
                        $sender->sendMessage("§eName: ".$value[0]."§r§e | World: ".$value[1]);
                    }
                }
            }
        }
        return true;
    }

    public function serverList($player){
        $form = new SimpleForm(function (Player $player, $data){
            if ($data === null){
                return;
            }
            else{
                $value = explode(":", $this->list[$data]);
                $value = str_replace("&", "§", $value);
                if($this->tranType == 0){
                    $this->getServer()->getCommandMap()->dispatch($player, 'transferserver '.$value[1].' '.$value[2]);
                }
                elseif($this->tranType ==1){
                    $cmdStr = $this->config->get("World-CMD");
                    $cmdStr = str_replace("{player}", $player, $cmdStr);
                    $cmdStr = str_replace("{world}", $value[1], $cmdStr);
                    if ($this->cmdMode == 0){
                        $this->getServer()->getCommandMap()->dispatch($player, $cmdStr);
                    }
                    else{
                        $this->getServer()->getCommandMap()->dispatch(new ConsoleCommandSender(), $cmdStr);
                    }
                }
            }
            return true;
        });
        $form->setTitle($this->config->get("UI-Title"));
        $form->setContent($this->config->get("UI-Message"));
        foreach ($this->list as $target) {
            $value = explode(":", $target);
            $value = str_replace("&", "§", $value);
            unset($search);
            if($this->tranType === 0){
                if(isset($value[3])){
                    $search = $value[3];
                    $file = $value[4];
                }
            }
            else{
                if(isset($value[2])){
                    $search = $value[2];
                    $file = $value[3];
                }
            }
            if(isset($search)){
                if($search == "url"){
                    $form->addButton($value[0]."\n§r§o§8Tap to transfer", 1, "http://".$file);
                }
                if($search == "path"){
                    $form->addButton($value[0]."\n§r§o§8Tap to transfer", 0, $file);
                }
            }
            else{
                $form->addButton($value[0]);
            }
        }
        $form->sendToPlayer($player);
    }

    public function onJoin(PlayerJoinEvent $event){
        if ($this->selectorSupport == true) {
            $player = $event->getPlayer();
            $selectorText = $this->config->get("Selector-Name");
            $selectorText = str_replace("&", "§", $selectorText);
            $enchantment = Enchantment::getEnchantment(0);
            $enchInstance = new EnchantmentInstance($enchantment, 1);
            $itemType = $this->config->get("Selector-Item");
            $item = Item::get($itemType);
            $item->setCustomName("§o$selectorText");
            $item->addEnchantment($enchInstance);
            $slot = $itemType = $this->config->get("Selector-Slot");
            $player->getInventory()->setItem($slot,$item,true);
        }
    }

    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        $items = $player->getInventory()->getContents();
        $selectorText = $this->config->get("Selector-Name");
        $selectorText = str_replace("&", "§", $selectorText);
        foreach ($items as $target) {
            if ($target->getCustomName() == "§o$selectorText") {
                $player->getInventory()->remove($target);
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event){
        if ($this->selectorSupport == true) {
            $player = $event->getPlayer();
            $selectorText = $this->config->get("Selector-Name");
            $selectorText = str_replace("&", "§", $selectorText);
            $itemType = $this->config->get("Selector-Item");
            $item = $player->getInventory()->getItemInHand();
            if ($item->getCustomName() == "§o$selectorText" && $item->getId() == $itemType){
                $this->serverList($player);
            }
        }
    }

    public function onDrop(PlayerDropItemEvent $event){
        if ($this->selectorSupport == true) {
            $player = $event->getPlayer();
            $selectorText = $this->config->get("Selector-Name");
            $selectorText = str_replace("&", "§", $selectorText);
            $itemType = $this->config->get("Selector-Item");
            $item = $player->getInventory()->getItemInHand();
            if ($item->getCustomName() == "§o$selectorText" && $item->getId() == $itemType){
                $event->setCancelled();
            }
        }
    }
}
