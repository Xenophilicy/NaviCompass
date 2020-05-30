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

use pocketmine\command\{Command, CommandSender, PluginCommand};
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerInteractEvent, PlayerJoinEvent, PlayerQuitEvent};
use pocketmine\inventory\transaction\action\{DropItemAction, SlotChangeAction};
use pocketmine\item\enchantment\{Enchantment, EnchantmentInstance};
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\{Config, TextFormat as TF};
use Xenophilicy\NaviCompass\libs\jojoe77777\FormAPI\SimpleForm;
use Xenophilicy\NaviCompass\Task\CompassCooldownTask;
use Xenophilicy\NaviCompass\Task\QueryTaskCaller;
use Xenophilicy\NaviCompass\Task\TeleportTask;
use Xenophilicy\NaviCompass\Task\TransferTask;

/**
 * Class NaviCompass
 * @package Xenophilicy\NaviCompass
 */
class NaviCompass extends PluginBase implements Listener {
    
    /**
     * @var array
     */
    public static $settings;
    /**
     * @var NaviCompass
     */
    private static $plugin;
    public $compassCooldown = [];
    private $queryResults;
    private $list;
    private $enchInst;
    
    /**
     * @return mixed
     */
    public static function getPlugin(){
        return self::$plugin;
    }
    
    public function onEnable(){
        self::$plugin = $this;
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $configPath = $this->getDataFolder() . "config.yml";
        if(!file_exists($configPath)){
            $this->getLogger()->notice("It appears that this is the first time you are using NaviCompass! Before reporting that the plugin doesn't work, please be sure your config file is setup correctly.");
        }
        $this->saveDefaultConfig();
        $this->config = new Config($configPath, Config::YAML);
        self::$settings = $this->config->getAll();
        $configVersion = self::$settings["VERSION"];
        $pluginVersion = $this->getDescription()->getVersion();
        if($configVersion < "2.3.0"){
            $this->getLogger()->warning("You have updated NaviCompass to v" . $pluginVersion . " but have a config from v$configVersion! Please delete your old config for new features to be enabled and to prevent unwanted errors!");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        if(self::$settings["Selector"]["Enabled"]) $this->createEnchant();
        if(self::$settings["Command"]["Enabled"]){
            $cmdName = str_replace("/", "", self::$settings["Command"]["Name"]);
            if($cmdName == null || $cmdName == ""){
                $this->getLogger()->critical("Invalid UI command string found, disabling plugin...");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
            }else{
                $cmd = new PluginCommand($cmdName, $this);
                $cmd->setDescription(self::$settings["Command"]["Description"]);
                if(self::$settings["Command"]["Permission"]["Enabled"]){
                    $cmd->setPermission(self::$settings["Command"]["Permission"]["Node"]);
                }
                $this->getServer()->getCommandMap()->register("NaviCompass", $cmd, $cmdName);
            }
        }else{
            $this->getLogger()->info("Command method disabled in config...");
        }
        $transferType = self::$settings["Transfer-Type"];
        switch(strtolower($transferType)){
            case "external":
                break;
            case "hybrid":
            case "internal":
                $cmd = self::$settings["World-CMD"];
                if($cmd == null || $cmd == ""){
                    $this->getLogger()->critical("Invalid transfer type! Input type: " . $transferType . " not supported, disabling plugin!");
                    $this->getServer()->getPluginManager()->disablePlugin($this);
                    return;
                }else{
                    $mode = strtolower(self::$settings["World-CMD-Mode"]);
                    if($mode !== "player" && $mode !== "console"){
                        $this->getLogger()->critical("Invalid world command mode found! Input mode: " . $transferType . " not supported, disabling plugin!");
                        $this->getServer()->getPluginManager()->disablePlugin($this);
                        return;
                    }
                }
                break;
            default:
                $this->getLogger()->critical("Invalid transfer type! Input type: " . $transferType . " not supported, disabling plugin!");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
        }
        $this->list = [];
        foreach(self::$settings["List"] as $target){
            unset($search);
            $value = explode(":", $target);
            $mode = strtolower($value[0]);
            switch($mode){
                case "ext":
                    if(isset($value[4])){
                        $search = $value[4];
                    }
                    $this->queryResults[$value[2] . ":" . $value[3]] = [];
                    $this->startQueryTask($value[2], $value[3]);
                    break;
                case "int":
                case "wd":
                    if(isset($value[3])){
                        $search = $value[3];
                        if(!isset($value[4]) || $value[4] === ""){
                            $this->getLogger()->warning("Invalid path/URL! Input: " . $value[1]);
                            continue 2;
                        }
                    }
                    break;
                default:
                    $this->getLogger()->warning("Invalid listing type! Invalid type: " . $value[0]);
                    continue 2;
                    break;
            }
            if(isset($search)){
                if(!$this->checkImagePath($value, $search)) continue;
            }
            array_push($this->list, $target);
        }
    }
    
    private function createEnchant(){
        Enchantment::registerEnchantment(new Enchantment(100, "", 0, 0, 0, 1));
        $enchantment = Enchantment::getEnchantment(100);
        $this->enchInst = new EnchantmentInstance($enchantment, 1);
    }
    
    /**
     * @param string $host
     * @param int $port
     */
    private function startQueryTask(string $host, int $port){
        $this->getScheduler()->scheduleRepeatingTask(new QueryTaskCaller($this, $host, $port), 200);
    }
    
    private function checkImagePath(array $value, string $search): bool{
        switch(strtolower($search)){
            case'url':
            case'path':
                break;
            default:
                $this->getLogger()->warning("Invalid image type! Input: " . $value[1] . TF::RESET . TF::YELLOW . " Image type: " . $search . TF::RESET . TF::YELLOW . " not supported.");
                return false;
        }
        return true;
    }
    
    /**
     * @param $result
     * @param string $host
     * @param int $port
     */
    public function queryTaskCallback($result, string $host, int $port){
        $this->queryResults[$host . ":" . $port] = $result;
    }
    
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool{
        if($command->getName() == "navicompass"){
            $sender->sendMessage(TF::GRAY . "---" . TF::GOLD . " NaviCompass " . TF::GRAY . "---");
            $sender->sendMessage(TF::YELLOW . "Version: " . TF::AQUA . $this->getDescription()->getVersion());
            $sender->sendMessage(TF::YELLOW . "Description: " . TF::AQUA . "View servers or worlds");
            if(self::$settings["Selector"]["Enabled"]){
                $sender->sendMessage(TF::YELLOW . "Selector: " . TF::GREEN . "Enabled");
            }else{
                $sender->sendMessage(TF::YELLOW . "Selector: " . TF::RED . "Disabled");
            }
            if(self::$settings["Command"]["Enabled"]){
                $sender->sendMessage(TF::YELLOW . "Command: " . TF::GREEN . "Enabled");
                $sender->sendMessage(TF::LIGHT_PURPLE . " - " . TF::BLUE . "/" . str_replace("/", "", self::$settings["Command"]["Name"]));
            }else{
                $sender->sendMessage(TF::YELLOW . "Command: " . TF::RED . "Disabled");
            }
            $sender->sendMessage(TF::GRAY . "-------------------");
        }
        if(self::$settings["Command"]["Enabled"] && $command->getName() == str_replace("/", "", self::$settings["Command"]["Name"])){
            if($sender instanceof Player){
                $this->serverList($sender);
            }else{
                $sender->sendMessage(" " . self::$settings["UI"]["Title"]);
                foreach($this->list as $target){
                    $value = explode(":", $target);
                    $value = str_replace("&", "§", $value);
                    switch(strtolower($value[0])){
                        case "ext":
                            $sender->sendMessage(TF::YELLOW . "Name: " . $value[1] . TF::RESET . TF::YELLOW . " | IP: " . $value[2] . " | Port: " . $value[3]);
                            break;
                        case "int":
                            $sender->sendMessage(TF::YELLOW . "Name: " . $value[1] . TF::RESET . TF::YELLOW . " | Alias: " . $value[2]);
                            break;
                        case "wd":
                            $sender->sendMessage(TF::YELLOW . "Name: " . $value[1] . TF::RESET . TF::YELLOW . " | Server: " . $value[2]);
                            break;
                    }
                }
            }
        }
        return true;
    }
    
    /**
     * @param Player $player
     */
    public function serverList(Player $player){
        if(!in_array(self::$settings["Sounds"]["UI"], [false, "false", "off"])){
            $this->playSound(self::$settings["Sounds"]["UI"], $player);
        }
        $form = new SimpleForm(function(Player $player, $data){
            if($data === null || count($this->list) === 0){
                return;
            }else{
                $value = explode(":", $this->list[$data]);
                $value = str_replace("&", "§", $value);
                $delay = self::$settings["Titles"]["Delay"];
                switch(strtolower($value[0])){
                    case "ext":
                        $this->sendActions("Transfer", $player);
                        $this->getScheduler()->scheduleDelayedTask(new TransferTask($value[2], $value[3], $player), $delay * 20);
                        break;
                    case "int":
                        $cmdStr = self::$settings["World-CMD"];
                        $cmdStr = str_replace("{player}", $player->getName(), $cmdStr);
                        $cmdStr = str_replace("{world}", $value[2], $cmdStr);
                        $this->sendActions("Teleport", $player);
                        $this->getScheduler()->scheduleDelayedTask(new TeleportTask($this, $cmdStr, $player), $delay * 20);
                        break;
                    case "wd":
                        $cmdStr = "server " . $value[2];
                        $this->sendActions("Transfer", $player);
                        $this->getScheduler()->scheduleDelayedTask(new TeleportTask($this, $cmdStr, $player, true), $delay * 20);
                        break;
                }
            }
            return;
        });
        if(count($this->list) === 0){
            $form->setTitle(TF::RED . "Nothing to see here");
            $form->setContent(TF::YELLOW . "You have not added any servers or worlds in the config.yml file yet! Add some and you'll see them appear here in the UI!");
            $form->addButton(TF::RED . "Close");
            $player->sendForm($form);
            return;
        }
        $form->setTitle(self::$settings["UI"]["Title"]);
        $form->setContent(self::$settings["UI"]["Message"]);
        foreach($this->list as $target){
            $value = explode(":", $target);
            $value = str_replace("&", "§", $value);
            unset($search);
            $file = "";
            $subtext = "";
            switch(strtolower($value[0])){
                case "ext":
                    $subtext = self::$settings["UI"]["Subtext"]["Server"];
                    $queryResult = $this->queryResults[$value[2] . ":" . $value[3]];
                    if($queryResult[0] === "online"){
                        $subtext = str_replace("{status}", self::$settings["UI"]["Status-Format"]["Online"] . TF::RESET, $subtext);
                        $subtext = str_replace("{current-players}", $queryResult[1], $subtext);
                        $subtext = str_replace("{max-players}", $queryResult[2], $subtext);
                    }else{
                        $subtext = str_replace("{status}", self::$settings["UI"]["Status-Format"]["Offline"] . TF::RESET, $subtext);
                        $subtext = str_replace("{current-players}", "-", $subtext);
                        $subtext = str_replace("{max-players}", "-", $subtext);
                    }
                    if(isset($value[4])){
                        $search = $value[4];
                        $file = $value[5];
                    }
                    break;
                case "int":
                    $subtext = self::$settings["UI"]["Subtext"]["World"];
                    $this->getServer()->loadLevel($value[2]);
                    $level = $this->getServer()->getLevelByName($value[2]);
                    if(is_null($level)){
                        $worldPlayerCount = 0;
                    }else{
                        $worldPlayerCount = sizeof($level->getPlayers());
                    }
                    $subtext = str_replace("{current-players}", $worldPlayerCount, $subtext);
                    if(isset($value[3])){
                        $search = $value[3];
                        $file = $value[4];
                    }
                    break;
                case "wd":
                    $subtext = self::$settings["UI"]["Subtext"]["WaterDog"];
                    if(isset($value[3])){
                        $search = $value[3];
                        $file = $value[4];
                    }
                    break;
            }
            if(isset($search)){
                if($search == "url"){
                    $form->addButton($value[1] . "\n" . $subtext, 1, "http://" . $file);
                }
                if($search == "path"){
                    $form->addButton($value[1] . "\n" . $subtext, 0, str_replace("++", ":", $file));
                }
            }else{
                $form->addButton($value[1] . "\n" . $subtext);
            }
        }
        $player->sendForm($form);
    }
    
    /**
     * @param string $soundName
     * @param Player $player
     */
    public function playSound(string $soundName, Player $player){
        $sound = new PlaySoundPacket();
        $sound->x = $player->getX();
        $sound->y = $player->getY();
        $sound->z = $player->getZ();
        $sound->volume = 1;
        $sound->pitch = 1;
        $sound->soundName = $soundName;
        $this->getServer()->broadcastPacket([$player], $sound);
    }
    
    /**
     * @param string $type
     * @param Player $player
     */
    private function sendActions(string $type, Player $player){
        if(!in_array(self::$settings["Sounds"][$type], [false, "false", "off"])){
            $this->playSound(self::$settings["Sounds"][$type], $player);
        }
        if(!in_array(self::$settings["Titles"][$type], [false, "false", "off"])){
            $player->addTitle(self::$settings["Titles"][$type]);
        }
    }
    
    /**
     * @param PlayerJoinEvent $event
     */
    public function onJoin(PlayerJoinEvent $event){
        if(self::$settings["Selector"]["Enabled"]){
            $player = $event->getPlayer();
            $item = Item::get(self::$settings["Selector"]["Item"]);
            $item->setCustomName(self::$settings["Selector"]["Name"]);
            $item->setLore([self::$settings["Selector"]["Lore"]]);
            $item->addEnchantment($this->enchInst);
            $slot = self::$settings["Selector"]["Slot"];
            $player->getInventory()->setItem($slot, $item, true);
        }
    }
    
    /**
     * @param PlayerQuitEvent $event
     */
    public function onQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        $items = $player->getInventory()->getContents();
        foreach($items as $target){
            if($this->isSelectorItem($target)){
                $player->getInventory()->remove($target);
            }
        }
    }
    
    private function isSelectorItem(Item $item): bool{
        if(self::$settings["Selector"]["Enabled"]){
            if($item->getCustomName() == self::$settings["Selector"]["Name"] && $item->getId() == self::$settings["Selector"]["Item"] && $item->getLore() == [self::$settings["Selector"]["Lore"]]){
                return true;
            }
        }
        return false;
    }
    
    /**
     * @param PlayerInteractEvent $event
     */
    public function onInteract(PlayerInteractEvent $event){
        if(self::$settings["Selector"]["Enabled"]){
            $player = $event->getPlayer();
            $item = $player->getInventory()->getItemInHand();
            if(!$this->isSelectorItem($item)) return;
            $event->setCancelled();
            if(self::$settings["Selector"]["Cooldown"]["Enabled"]){
                $msg = self::$settings["Selector"]["Cooldown"]["Message"];
                if(isset($this->compassCooldown[$player->getName()])){
                    if($msg) $player->sendPopup($msg);
                    return;
                }
                $this->compassCooldown[$player->getName()] = true;
                $this->getScheduler()->scheduleDelayedTask(new CompassCooldownTask($this, $player), self::$settings["Selector"]["Cooldown"]["Duration"] * 20);
            }
            $this->serverList($player);
        }
    }
    
    /**
     * @param InventoryTransactionEvent $event
     */
    public function onInventoryTransaction(InventoryTransactionEvent $event){
        if(self::$settings["Selector"]["Enabled"] && self::$settings["Selector"]["Force-Slot"]){
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
