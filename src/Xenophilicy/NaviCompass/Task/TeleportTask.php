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

namespace Xenophilicy\NaviCompass\Task;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\network\mcpe\protocol\ScriptCustomEventPacket;
use pocketmine\utils\Binary;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use Xenophilicy\NaviCompass\NaviCompass;

/**
 * Class TeleportTask
 * @package Xenophilicy\NaviCompass\Task
 */
class TeleportTask extends Task {
    
    private $plugin;
    private $cmdString;
    private $player;
    private $waterdog;
    
    /**
     * TeleportTask constructor.
     * @param NaviCompass $plugin
     * @param string $cmdString
     * @param Player $player
     * @param bool $waterdog
     */
    public function __construct(NaviCompass $plugin, string $cmdString, Player $player, bool $waterdog = false){
        $this->plugin = $plugin;
        $this->cmdString = $cmdString;
        $this->player = $player;
        $this->waterdog = $waterdog;
    }
    
    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick){
        if($this->waterdog){
	    $pk = new ScriptCustomEventPacket();
	    $pk->eventName = "bungeecord:main";
	    $pk->eventData = Binary::writeShort(strlen("Connect")) . "Connect" . Binary::writeShort(strlen($this->cmdString)) . $this->cmdString;
	    $this->player->sendDataPacket($pk);
	    return;
	}
        if(strtolower(NaviCompass::$settings["World-CMD-Mode"]) == "player"){
            $this->plugin->getServer()->getCommandMap()->dispatch($this->player, $this->cmdString);
        }else if(strtolower(NaviCompass::$settings["World-CMD-Mode"]) == "console"){
            $this->plugin->getServer()->getCommandMap()->dispatch(new ConsoleCommandSender(), $this->cmdString);
        }
    }
}
