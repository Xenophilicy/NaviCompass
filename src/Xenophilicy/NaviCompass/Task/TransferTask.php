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

use pocketmine\Player;
use pocketmine\scheduler\Task;
use Xenophilicy\NaviCompass\NaviCompass;

/**
 * Class TransferTask
 * @package Xenophilicy\NaviCompass\Task
 */
class TransferTask extends Task {
    
    private $plugin;
    private $host;
    private $port;
    private $player;
    
    /**
     * TransferTask constructor.
     * @param NaviCompass $plugin
     * @param string $host
     * @param int $port
     * @param Player $player
     */
    public function __construct(NaviCompass $plugin, string $host, int $port, Player $player){
        $this->plugin = $plugin;
        $this->host = $host;
        $this->port = $port;
        $this->player = $player;
    }
    
    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick){
        $this->player->transfer($this->host, $this->port);
    }
}