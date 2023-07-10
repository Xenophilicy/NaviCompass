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

use pocketmine\console\ConsoleCommandSender;
use pocketmine\network\mcpe\protocol\ScriptCustomEventPacket;
use pocketmine\network\mcpe\protocol\TransferPacket;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\Binary;
use Xenophilicy\NaviCompass\NaviCompass;

/**
 * Class TeleportTask
 * @package Xenophilicy\NaviCompass\Task
 */
class TeleportTask extends Task {
    
    private NaviCompass $plugin;
    private string $cmdString;
    private Player $player;
    private bool $waterdog;
    
    /**
     * TeleportTask constructor.
     * @param NaviCompass $plugin
     * @param string $cmdString
     * @param Player $player
     * @param bool $waterdog
     */
    public function __construct(NaviCompass $plugin, string $cmdString, Player $player, bool $waterdog = false) {
        $this->plugin = $plugin;
        $this->cmdString = $cmdString;
        $this->player = $player;
        $this->waterdog = $waterdog;
    }

    public function onRun(): void {
        if($this->waterdog) {
            $pk = new TransferPacket();
            $pk->address = $this->cmdString;
            $this->player->getNetworkSession()->sendDataPacket($pk);
            return;
        }
        if(strtolower(NaviCompass::$settings["World-CMD-Mode"]) == "player") {
            $this->plugin->getServer()->getCommandMap()->dispatch($this->player, $this->cmdString);
        } else if(strtolower(NaviCompass::$settings["World-CMD-Mode"]) == "console") {
            $this->plugin->getServer()->getCommandMap()->dispatch(new ConsoleCommandSender($this->plugin->getServer(), $this->plugin->getServer()->getLanguage()), $this->cmdString);
        }
    }
}
