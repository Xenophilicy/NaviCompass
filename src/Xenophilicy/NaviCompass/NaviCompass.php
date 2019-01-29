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
use pocketmine\{Server,Player};
use pocketmine\utils\Config;
use pocketmine\item\Item;
use pocketmine\inventory\Inventory;

use Xenophilicy\NaviCompass\libs\jojoe77777\FormAPI\SimpleForm;

class NaviCompass extends PluginBase implements Listener {

    private $config;

    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();
        $this->config = new Config($this->getDataFolder()."config.yml", Config::YAML);
        $this->config->getAll();
        $this->getLogger()->info("NaviCompass has been enabled!");
        $selectorEnable = $this->config->get("Selector-Support");
        if ($selectorEnable == true) {
            $this->selectorSupport = true;
        }
        else {
            $this->selectorSupport = false;
            $this->getLogger()->notice("§eSelector item support turned off in config! Disabling selector...");
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        $player = $sender->getPlayer();
        if ($command->getName() == "servers"){
            if ($sender instanceof Player) {
                $this->serverList($sender);
            }
            else{
                $sender->sendMessage(" ".$this->config->get("UI-Title"));
                foreach ($this->config->getAll("Servers") as $servers) {
                    $value = explode(":", $servers);
                    $sender->sendMessage("Name: ".$value[0]." | IP: ".$value[1]." | Port: ".$value[2]);
                }
            }
        }
        return true;
    }

    public function serverList($player){
        $formapi = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = new SimpleForm(function (Player $player, $data){
            if ($data === null){
                return;
            }
            if(isset($data[0])){
                $value = explode(":", $this->config[0]);

            }
            return true;
        });
        $form->setTitle($this->config->get("UI-Title"));
        $form->setContent($this->config->get("UI-Message"));
        foreach ($this->config->getAll("Servers") as $servers) {
            $value = explode(":", $servers);
            $form->addButton($value[0]);
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
            $player->getInventory()->addItem($item);
        }
    }

    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        $items = $player->getInventory()->getContents();
        $selectorText = $this->config->get("Selector-Name");
        $selectorText = str_replace("&", "§", $selectorText);
        foreach($items as $target) {
            if ($target->getCustomName() == "§o$selectorText") {
                $player->getInventory()->remove($target);
            }
            return false;
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
}
