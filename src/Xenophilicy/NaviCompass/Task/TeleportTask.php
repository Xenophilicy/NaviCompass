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
    
    /**
     * TeleportTask constructor.
     * @param NaviCompass $plugin
     * @param string $cmdString
     * @param Player $player
     */
    public function __construct(NaviCompass $plugin, string $cmdString, Player $player){
        $this->plugin = $plugin;
        $this->cmdString = $cmdString;
        $this->player = $player;
    }
    
    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick){
        if($this->plugin->cmdMode == 0){
            $this->plugin->getServer()->getCommandMap()->dispatch($this->player, $this->cmdString);
        }else{
            $this->plugin->getServer()->getCommandMap()->dispatch(new ConsoleCommandSender(), $this->cmdString);
        }
    }
}
