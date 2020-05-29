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
use pocketmine\level\sound\AnvilBreakSound;
use pocketmine\level\sound\AnvilFallSound;
use pocketmine\level\sound\AnvilUseSound;
use pocketmine\level\sound\BlazeShootSound;
use pocketmine\level\sound\ClickSound;
use pocketmine\level\sound\DoorBumpSound;
use pocketmine\level\sound\DoorCrashSound;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\level\sound\FizzSound;
use pocketmine\level\sound\GhastShootSound;
use pocketmine\level\sound\GhastSound;
use pocketmine\level\sound\LaunchSound;
use pocketmine\level\sound\PopSound;
use pocketmine\level\sound\Sound;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\{Config, TextFormat as TF};
use Xenophilicy\NaviCompass\libs\jojoe77777\FormAPI\SimpleForm;
use Xenophilicy\NaviCompass\Task\QueryTaskCaller;
use Xenophilicy\NaviCompass\Task\TeleportTask;
use Xenophilicy\NaviCompass\Task\TransferTask;

/**
 * Class NaviCompass
 * @package Xenophilicy\NaviCompass
 */
class NaviCompass extends PluginBase implements Listener {
    
    private static $plugin;
    /**
     * @var int
     */
    public $cmdMode;
    public $transferSound;
    /**
     * @var mixed|null
     */
    public $teleportTitle;
    /**
     * @var mixed|null
     */
    public $delay;
    /**
     * @var mixed|null
     */
    public $transferTitle;
    /**
     * @var mixed|null
     */
    public $teleportSound;
    /**
     * @var string
     */
    private $pluginVersion;
    /**
     * @var bool
     */
    private $selectorSupport;
    /**
     * @var string
     */
    private $selectorName;
    /**
     * @var array
     */
    private $selectorLore;
    /**
     * @var mixed|null
     */
    private $itemType;
    /**
     * @var mixed|null
     */
    private $forceSlot;
    /**
     * @var bool
     */
    private $commandSupport;
    /**
     * @var mixed|string|string[]|null
     */
    private $cmdName;
    private $enchInst;
    /**
     * @var array
     */
    private $list;
    private $queryResults;
    /**
     * @var mixed|null
     */
    private $openSound;
    
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
            $this->getLogger()->critical("It appears that this is the first time you are using NaviCompass! This plugin does not function with the default config.yml, so please edit it to your preferred settings before attempting to use it.");
            $this->saveDefaultConfig();
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        $this->saveDefaultConfig();
        $this->config = new Config($configPath, Config::YAML);
        $this->config->getAll();
        $configVersion = $this->config->get("VERSION");
        $this->pluginVersion = $this->getDescription()->getVersion();
        if($configVersion < "2.2.0"){
            $this->getLogger()->warning("You have updated NaviCompass to v" . $this->pluginVersion . " but have a config from v$configVersion! Please delete your old config for new features to be enabled and to prevent unwanted errors!");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        if($this->config->getNested("Selector.Enabled")){
            $this->selectorSupport = true;
            $this->createEnchant();
            $this->selectorName = TF::ITALIC . (str_replace("&", "§", $this->config->getNested("Selector.Name")));
            $this->selectorLore = [str_replace("&", "§", $this->config->getNested("Selector.Lore"))];
            $this->itemType = $this->config->getNested("Selector.Item");
            $this->forceSlot = $this->config->getNested("Selector.Force-Slot");
        }else{
            $this->selectorSupport = false;
            $this->getLogger()->info("Selector item disabled in config...");
        }
        if($this->config->getNested("Command.Enabled")){
            $this->commandSupport = true;
            $this->cmdName = str_replace("/", "", $this->config->getNested("Command.Name"));
            if($this->cmdName == null || $this->cmdName == ""){
                $this->getLogger()->critical("Invalid UI command string found, disabling plugin...");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
            }else{
                $cmd = new PluginCommand($this->cmdName, $this);
                $cmd->setDescription($this->config->getNested("Command.Description"));
                if($this->config->getNested("Command.Permission.Enabled")){
                    $cmd->setPermission($this->config->getNested("Command.Permission.Node"));
                }
                $this->getServer()->getCommandMap()->register("NaviCompass", $cmd, $this->cmdName);
            }
        }else{
            $this->commandSupport = false;
            $this->getLogger()->info("Command method disabled in config...");
        }
        $transferType = $this->config->get("Transfer-Type");
        switch(strtolower($transferType)){
            case "external":
                $extLimit = false;
                break;
            case "hybrid":
            case "internal":
                if($this->config->get("World-CMD") == null || $this->config->get("World-CMD") == ""){
                    $this->getLogger()->critical("Null world command string found, limiting to external use!");
                    $extLimit = true;
                }else{
                    $mode = $this->config->get("World-CMD-Mode");
                    if(strtolower($mode) == "player"){
                        $extLimit = false;
                        $this->cmdMode = 0;
                    }elseif(strtolower($mode) == "console"){
                        $extLimit = false;
                        $this->cmdMode = 1;
                    }else{
                        $this->getLogger()->critical("Null world command mode found, limiting to external use!");
                        $extLimit = true;
                    }
                }
                break;
            case false:
            case null:
                $this->getLogger()->critical("Null transfer type found, disabling plugin!");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
            default:
                $this->getLogger()->critical("Invalid transfer type! Input type: " . $transferType . " not supported, disabling plugin!");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
        }
        $this->list = [];
        foreach($this->config->get("List") as $target){
            unset($search);
            $value = explode(":", $target);
            if(strtolower($value[0]) === "ext"){
                if(isset($value[4])){
                    $search = $value[4];
                }
                $this->queryResults[$value[2] . ":" . $value[3]] = [];
                $this->startQueryTask($value[2], $value[3]);
            }elseif(strtolower($value[0]) === "int" && !$extLimit){
                if(!$this->getServer()->isLevelGenerated($value[2])){
                    if($value[2] == "xenoCreative"){
                        $this->getLogger()->critical("This plugin does not function with the default config.yml, so please edit it to your preferred settings before attempting to use it. Plugin will remain disabled until default config is changed.");
                        $this->getServer()->getPluginManager()->disablePlugin($this);
                        return;
                    }else{
                        $this->getLogger()->critical("Invalid world name! Name: " . $value[2] . " was not found, be sure to use the name of the world folder for the 'WorldAlias' key in the config!");
                        continue;
                    }
                }
                if(isset($value[3])){
                    $search = $value[3];
                    if(!isset($value[4]) || $value[4] === ""){
                        $this->getLogger()->warning("Null path/URL! Input: " . $value[1]);
                        continue;
                    }
                }
                if(isset($search)){
                    switch(strtolower($search)){
                        case'url':
                        case'path':
                            break;
                        default:
                            $this->getLogger()->warning("Invalid image type! Input: " . $value[1] . TF::RESET . TF::YELLOW . " Image type: " . $search . TF::RESET . TF::YELLOW . " not supported. ");
                            continue 2;
                    }
                }
            }
            $this->teleportSound = $this->config->getNested("Sounds.Teleport");
            $this->transferSound = $this->config->getNested("Sounds.Transfer");
            $this->openSound = $this->config->getNested("Sounds.UI");
            $this->teleportTitle = $this->config->getNested("Titles.Teleport");
            $this->transferTitle = $this->config->getNested("Titles.Transfer");
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
            $sender->sendMessage(TF::YELLOW . "Version: " . TF::AQUA . $this->pluginVersion);
            $sender->sendMessage(TF::YELLOW . "Description: " . TF::AQUA . "View servers or worlds");
            if($this->selectorSupport){
                $sender->sendMessage(TF::YELLOW . "Selector: " . TF::GREEN . "Enabled");
            }else{
                $sender->sendMessage(TF::YELLOW . "Selector: " . TF::RED . "Disabled");
            }
            if($this->commandSupport){
                $sender->sendMessage(TF::YELLOW . "Command: " . TF::GREEN . "Enabled");
                $sender->sendMessage(TF::LIGHT_PURPLE . " - " . TF::BLUE . "/" . $this->cmdName);
            }else{
                $sender->sendMessage(TF::YELLOW . "Command: " . TF::RED . "Disabled");
            }
            $sender->sendMessage(TF::GRAY . "-------------------");
        }
        if($this->commandSupport && $command->getName() == $this->cmdName){
            if($sender instanceof Player){
                $this->serverList($sender);
            }else{
                $sender->sendMessage(" " . $this->config->getNested("UI.Title"));
                foreach($this->list as $target){
                    $value = explode(":", $target);
                    $value = str_replace("&", "§", $value);
                    if(strtolower($value[0]) == "ext"){
                        $sender->sendMessage(TF::YELLOW . "Name: " . $value[1] . TF::RESET . TF::YELLOW . " | IP: " . $value[2] . " | Port: " . $value[3]);
                    }else{
                        $sender->sendMessage(TF::YELLOW . "Name: " . $value[1] . TF::RESET . TF::YELLOW . " | Alias: " . $value[2]);
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
        if(!in_array($this->openSound, [false, "false", "off"]) && ($sound = $this->getSound($this->openSound, $player)) !== null){
            $player->getLevel()->addSound($sound);
        }
        $form = new SimpleForm(function(Player $player, $data){
            if($data === null){
                return;
            }else{
                $value = explode(":", $this->list[$data]);
                $value = str_replace("&", "§", $value);
                if(strtolower($value[0]) == "ext"){
                    if(!in_array($this->transferSound, [false, "false", "off"]) && ($sound = $this->getSound($this->transferSound, $player)) !== null){
                        $player->getLevel()->addSound($sound);
                    }
                    if(!in_array($this->transferTitle, [false, "false", "off"])){
                        $player->addTitle($this->transferTitle);
                    }
                    $this->getScheduler()->scheduleDelayedTask(new TransferTask($this, $value[2], $value[3], $player), $this->config->getNested("Titles.Delay") * 20);
                }else{
                    $cmdStr = $this->config->get("World-CMD");
                    $cmdStr = str_replace("{player}", $player->getName(), $cmdStr);
                    $cmdStr = str_replace("{world}", $value[2], $cmdStr);
                    if(!in_array($this->teleportSound, [false, "false", "off"]) && ($sound = $this->getSound($this->teleportSound, $player)) !== null){
                        $player->getLevel()->addSound($sound);
                    }
                    if(!in_array($this->teleportTitle, [false, "false", "off"])){
                        $player->addTitle($this->teleportTitle);
                    }
                    $this->getScheduler()->scheduleDelayedTask(new TeleportTask($this, $cmdStr, $player), $this->config->getNested("Titles.Delay") * 20);
                }
            }
            return;
        });
        $form->setTitle($this->config->getNested("UI.Title"));
        $form->setContent($this->config->getNested("UI.Message"));
        foreach($this->list as $target){
            $value = explode(":", $target);
            $value = str_replace("&", "§", $value);
            unset($search);
            $file = "";
            if(strtolower($value[0]) == "ext"){
                $subtext = $this->config->getNested("UI.Server-Button-Subtext");
                $queryResult = $this->queryResults[$value[2] . ":" . $value[3]];
                if($queryResult[0] === "online"){
                    $subtext = str_replace("{status}", $this->config->getNested("UI.Status-Format.Online") . TF::RESET, $subtext);
                    $subtext = str_replace("{current-players}", $queryResult[1], $subtext);
                    $subtext = str_replace("{max-players}", $queryResult[2], $subtext);
                }else{
                    $subtext = str_replace("{status}", $this->config->getNested("UI.Status-Format.Offline") . TF::RESET, $subtext);
                    $subtext = str_replace("{current-players}", "-", $subtext);
                    $subtext = str_replace("{max-players}", "-", $subtext);
                }
                if(isset($value[4])){
                    $search = $value[4];
                    $file = $value[5];
                }
            }else{
                $subtext = $this->config->getNested("UI.World-Button-Subtext");
                $this->getServer()->loadLevel($value[2]);
                $worldPlayerCount = sizeof($this->getServer()->getLevelByName($value[2])->getPlayers());
                $subtext = str_replace("{current-players}", $worldPlayerCount, $subtext);
                if(isset($value[3])){
                    $search = $value[3];
                    $file = $value[4];
                }
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
    
    public function getSound(string $asString, Player $player): ?Sound{
        $pvec = $player->asVector3();
        switch($asString){
            case "anvil-break":
                return new AnvilBreakSound($pvec);
            case "anvil-fall":
                return new AnvilFallSound($pvec);
            case "anvil-use":
                return new AnvilUseSound($pvec);
            case "blaze-shoot":
                return new BlazeShootSound($pvec);
            case "click":
                return new ClickSound($pvec);
            case "door-bump":
                return new DoorBumpSound($pvec);
            case "door-crash":
                return new DoorCrashSound($pvec);
            case "enderman-teleport":
                return new EndermanTeleportSound($pvec);
            case "fizz":
                return new FizzSound($pvec);
            case "ghast-shoot":
                return new GhastShootSound($pvec);
            case "ghast":
                return new GhastSound($pvec);
            case "launch":
                return new LaunchSound($pvec);
            case "pop":
                return new PopSound($pvec);
            default:
                return null;
        }
    }
    
    /**
     * @param PlayerJoinEvent $event
     */
    public function onJoin(PlayerJoinEvent $event){
        if($this->selectorSupport){
            $player = $event->getPlayer();
            $item = Item::get($this->itemType);
            $item->setCustomName($this->selectorName);
            $item->setLore($this->selectorLore);
            $item->addEnchantment($this->enchInst);
            $slot = $this->config->getNested("Selector.Slot");
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
        if($this->selectorSupport){
            if($item->getCustomName() == $this->selectorName && $item->getId() == $this->itemType && $item->getLore() == $this->selectorLore){
                return true;
            }
        }
        return false;
    }
    
    /**
     * @param PlayerInteractEvent $event
     */
    public function onInteract(PlayerInteractEvent $event){
        if($this->selectorSupport){
            $player = $event->getPlayer();
            $item = $player->getInventory()->getItemInHand();
            if($this->isSelectorItem($item)){
                $this->serverList($player);
            }
        }
    }
    
    /**
     * @param InventoryTransactionEvent $event
     */
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
