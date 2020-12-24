<?php
namespace OguzhanUmutlu;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\plugin\Plugin;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\{Server,Player};
class Main extends PluginBase implements Listener {
    public function katilmaEvent(PlayerJoinEvent $event) {
      $player = $event->getPlayer();
      $data = $this->data;
      $messages = $this->messages;
      $friends = $this->data->get("friends");
      foreach($friends as $x) {
        if($x[0] == $player->getName()) {
          if($this->getServer()->getPlayer($x[1])) {
            $this->getServer()->getPlayer($x[1])->sendMessage(str_replace("%0", $player->getName(), $messages->getNested("friend-joined")));
          }
        } else if($x[1] == $player->getName()) {
          if($this->getServer()->getPlayer($x[0])) {
            $this->getServer()->getPlayer($x[0])->sendMessage(str_replace("%0", $player->getName(), $messages->getNested("friend-joined")));
          }
        }
      }
    }
    public function ayrilmaEvent(PlayerQuitEvent $event) {
      $player = $event->getPlayer();
      $data = $this->data;
      $messages = $this->messages;
      $friends = $this->data->get("friends");
      foreach($friends as $x) {
        if($x[0] == $player->getName()) {
          if($this->getServer()->getPlayer($x[1])) {
            $this->getServer()->getPlayer($x[1])->sendMessage(str_replace("%0", $player->getName(), $messages->getNested("friend-left")));
          }
        } else if($x[1] == $player->getName()) {
          if($this->getServer()->getPlayer($x[0])) {
            $this->getServer()->getPlayer($x[0])->sendMessage(str_replace("%0", $player->getName(), $messages->getNested("friend-left")));
          }
        }
      }
    }
    public function getArklar(Player $player): array {
      $arklar = [];
      foreach($this->data->get("friends") as $x) {
        if($x[0] == $player->getName()) {
          array_push($arklar, $x[1]);
        } else if($x[1] == $player->getName()) {
          array_push($arklar, $x[0]);
        }
      }
      return $arklar;
    }
    public function onEnable() {
      $this->getLogger()->info("FriendSystem enabling...");
      $this->saveResource("config.yml");
      $this->saveResource("messages.yml");
      $this->saveResource("data.yml");
      $this->getServer()->getPluginManager()->registerEvents($this, $this);
      @mkdir($this->getDataFolder());
      $this->config = new Config($this->getDataFolder()."config.yml", Config::YAML, array());
      $this->messages = new Config($this->getDataFolder()."messages.yml", Config::YAML, array());
      $this->data = new Config($this->getDataFolder()."data.yml", Config::YAML, array());
      $this->getServer()->getCommandMap()->register($this->messages->getNested("friendcommand.name"), new FriendCommand($this));
    }
    public function onDisable() {
      $this->getLogger()->info("FriendSystem disabling...");
    }
}