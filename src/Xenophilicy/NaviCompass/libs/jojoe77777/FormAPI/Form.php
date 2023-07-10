<?php

declare(strict_types=1);

namespace Xenophilicy\NaviCompass\libs\jojoe77777\FormAPI;

use pocketmine\form\Form as IForm;
use pocketmine\player\Player;

/**
 * Class Form
 * @package Xenophilicy\NaviCompass\libs\jojoe77777\FormAPI
 */
abstract class Form implements IForm {
    
    /** @var array */
    protected array $data = [];
    /** @var callable */
    private $callable;
    
    /**
     * @param callable|null $callable
     */
    public function __construct(?callable $callable) {
        $this->callable = $callable;
    }
    
    /**
     * @param Player $player
     * @see Player::sendForm()
     * @deprecated
     */
    public function sendToPlayer(Player $player): void {
        $player->sendForm($this);
    }
    
    /**
     * @param Player $player
     * @param mixed $data
     */
    public function handleResponse(Player $player, $data): void {
        $this->processData($data);
        $callable = $this->getCallable();
        if($callable !== null){
            $callable($player, $data);
        }
    }
    
    /**
     * @param $data
     */
    public function processData(&$data): void {
    }
    
    public function getCallable(): ?callable {
        return $this->callable;
    }
    
    /**
     * @param callable|null $callable
     */
    public function setCallable(?callable $callable) {
        $this->callable = $callable;
    }
    
    /**
     * @return array|mixed
     */
    public function jsonSerialize() {
        return $this->data;
    }
}
