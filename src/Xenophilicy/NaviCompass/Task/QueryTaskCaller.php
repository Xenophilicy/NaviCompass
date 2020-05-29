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

use pocketmine\scheduler\Task;
use Xenophilicy\NaviCompass\NaviCompass;

/**
 * Class QueryTaskCaller
 * @package Xenophilicy\NaviCompass\Task
 */
class QueryTaskCaller extends Task {
    
    private $plugin;
    private $host;
    private $port;
    
    /**
     * QueryTaskCaller constructor.
     * @param NaviCompass $plugin
     * @param string $host
     * @param int $port
     */
    public function __construct(NaviCompass $plugin, string $host, int $port){
        $this->plugin = $plugin;
        $this->host = $host;
        $this->port = $port;
    }
    
    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick){
        $this->plugin->getServer()->getAsyncPool()->submitTask(new QueryTask($this->host, $this->port));
    }
}