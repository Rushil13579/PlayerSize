<?php

namespace Rushil13579\PlayerSize;

use pocketmine\{Player, Server};

use pocketmine\plugin\PluginBase;

use pocketmine\command\{Command, CommandSender};

use pocketmine\entity\Entity;

use pocketmine\item\Item;

use pocketmine\network\mcpe\protocol\SetActorDataPacket;

use pocketmine\utils\{Config, TextFormat as C};

use jojoe77777\FormAPI\CustomForm;

class Main extends PluginBase {

  public $cfg;
  public $msg;

  public $size = [];

  public function onEnable(){
    $this->saveResource('config.yml');
    $this->saveResource('messages.yml');

    $this->cfg = $this->getConfig();
    $this->msg = new Config($this->getDataFolder() . "messages.yml", Config::YAML);

    $this->formapiCheck();
  }

  public function formapiCheck(){
    if($this->getServer()->getPluginManager()->getPlugin('FormAPI') === null){
      if($this->cfg->get('playersize-formapi-support') == true){
        $this->getLogger()->warning(C::colorize("&cFormAPI not found! Please disable formapi-support or install FormAPI"));
        $this->getServer()->getPluginManager()->disablePlugin($this);
      }
    }
  }

# ==================== PLAYERSIZE COMMAND ====================

  public function onCommand(CommandSender $sender, Command $cmd, String $label, Array $args) : bool {

    switch($cmd->getName()){
      case "playersize":

      if(!$sender instanceof Player){
        $sender->sendMessage(C::colorize($this->msg->get('not-player-msg')));
        return false;
      }

      if($this->cfg->get('playersize-permission-enabled') == true){
        if(!$sender->hasPermission($this->cfg->get('playersize-permission'))){
          $sender->sendMessage(C::colorize($this->msg->get('no-permission-msg')));
          return false;
        }
      }

      if(in_array($sender->getLevel()->getName(), $this->cfg->get('blacklisted-worlds'))){
        $sender->sendMessage(C::colorize($this->msg->get('blacklisted-world-msg')));
        return false;
      }

      if(!isset($args[0])){
        if($this->cfg->get('playersize-formapi-support') == true){
          $this->playersizeForm($sender);
          return false;
        }
        $sender->sendMessage(C::colorize($this->msg->get('playersize-usage-msg')));
        return false;
      }

      switch(strtolower($args[0])){

        case 'help':
          $sender->sendMessage(C::colorize($this->msg->get('playersize-help-msg')));
        break;

        case 'normal':
          $this->normal($sender);
        break;

        case 'small':
          $this->small($sender);
        break;

        case 'hide':
          $this->hide($sender);
        break;

        default:
          $sender->sendMessage(C::colorize($this->msg->get('playersize-usage-msg')));
        break;

      }
    }
    return true;
  }

# ==================== PLAYERSIZE FORM ====================

  public function playersizeForm($player){
    $form = new CustomForm(function (Player $player, array $data = null){
      if($data === null){
        return '';
      }
      if($data[0] == (int) '0'){
        $this->normal($player);
      }
      if($data[0] == (int) '1'){
        $this->small($player);
      }
      if($data[0] == (int) '2'){
        $this->hide($player);
      }
    });
    $form->setTitle(C::colorize($this->cfg->get('playersize-form-title')));
    $form->addStepSlider(C::colorize('&3PlayerSize'), [C::colorize('&aNormal'), C::colorize('&dSmall'), C::colorize('&cHide')]);
    $form->sendToPlayer($player);
    return $form;
  }

# ==================== PLAYERSIZE FUNCTIONS ====================

  public function normal($player){
    if(!isset($this->size[$player->getName()])){
      foreach($this->getServer()->getOnlinePlayers() as $pl){
        if($player->getName() !== $pl->getName()){
          $player->showPlayer($pl);
        }
      }
      $this->size[$player->getName()] = 'normal';
      $player->sendMessage(C::colorize($this->msg->get('playersize-normal-msg')));
    } else {
      if($this->size[$player->getName()] == 'small'){
        foreach($this->getServer()->getOnlinePlayers() as $pl){
          if($player->getName() !== $pl->getName()){
            $data = clone $pl->getDataPropertyManager();
            $data->setFloat(Entity::DATA_SCALE, '1');
            $pk = new SetActorDataPacket;
            $pk->entityRuntimeId = $pl->getId();
            $pk->metadata = $data->getDirty();
            $player->dataPacket($pk);
          }
        }
        $this->size[$player->getName()] = 'normal';
        $player->sendMessage(C::colorize($this->msg->get('playersize-normal-msg')));
      } else {
        foreach($this->getServer()->getOnlinePlayers() as $pl){
          if($player->getName() !== $pl->getName()){
            $player->showPlayer($pl);
          }
        }
        $this->size[$player->getName()] = 'normal';
        $player->sendMessage(C::colorize($this->msg->get('playersize-normal-msg')));
      }
    }
  }

  public function small($player){
    foreach($this->getServer()->getOnlinePlayers() as $pl){
      if($player->getName() !== $pl->getName()){
        $player->showPlayer($pl);
        $data = clone $pl->getDataPropertyManager();
        $data->setFloat(Entity::DATA_SCALE, '0.25');
        $pk = new SetActorDataPacket;
        $pk->entityRuntimeId = $pl->getId();
        $pk->metadata = $data->getDirty();
        $player->dataPacket($pk);
      }
    }
    $this->size[$player->getName()] = 'small';
    $player->sendMessage(C::colorize($this->msg->get('playersize-small-msg')));
  }

  public function hide($player){
    foreach($this->getServer()->getOnlinePlayers() as $pl){
      if($player->getName() !== $pl->getName()){
        $player->hidePlayer($pl);
      }
    }
    $this->size[$player->getName()] = 'hide';
    $player->sendMessage(C::colorize($this->msg->get('playersize-hide-msg')));
  }
}
