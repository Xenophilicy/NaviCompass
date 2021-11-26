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

use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use Xenophilicy\NaviCompass\NaviCompass;

/**
 * Class CompassCooldownTask
 * @package Xenophilicy\NaviCompass\Task
 */
class CompassCooldownTask extends Task {
    
    private NaviCompass $plugin;
    private Player $player;
    
    /**
     * CompassCooldownTask constructor.
     * @param NaviCompass $plugin
     * @param Player $player
     */
    public function __construct(NaviCompass $plugin, Player $player) {
        $this->plugin = $plugin;
        $this->player = $player;
    }
    
    /**
     * Actions to execute when run
     * @return void
     */
    public function onRun(): void {
        unset($this->plugin->compassCooldown[$this->player->getName()]);
    }
}