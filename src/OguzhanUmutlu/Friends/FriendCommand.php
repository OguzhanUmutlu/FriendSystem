<?php
declare(strict_types=1);
namespace OguzhanUmutlu\Friends;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\math\Vector3;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\level\Position;
class FriendCommand extends Command implements PluginIdentifiableCommand {
    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        parent::__construct(strtolower($plugin->messages->getNested("friendcommand.name")), $plugin->messages->getNested("friendcommand.description"), null, $plugin->messages->getNested("friendcommand.aliases"));
        if($plugin->messages->getNested("friendcommand.permission")) {
            $this->setPermission("friends.".$plugin->messages->getNested("friendcommand.permission"));
        }
    }
    public function mainMenu(Player $player) {
      $form = new SimpleForm(function (Player $player, int $data = null) {
        if($data != null) {
          if($data == 1) {
            $this->friendsMenu($player);
          }
          if($data == 2) {
            $this->sendRequestMenu($player);
          }
          if($data == 3) {
            $this->invitesPeopleMenu($player);
          }
          if($data == 4) {
            $this->invitesYouMenu($player);
          }
        }
      });
        
      $messages = $this->plugin->messages;
      $form->setTitle($messages->getNested("frienduioptions.mainmenu.title"));
      $form->setContent($messages->getNested("frienduioptions.mainmenu.content"));
      $form->addButton($messages->getNested("frienduioptions.exit"));
      $form->addButton($messages->getNested("frienduioptions.mainmenu.button-friends"));
      $form->addButton($messages->getNested("frienduioptions.mainmenu.button-invite"));
      $sayi = 0;
      foreach(array_keys($this->plugin->data->getNested("invites") ?? []) as $pp) {
        if($pp != $player->getName() && in_array($player->getName(),$this->plugin->data->getNested("invites")[$pp])) {
          $sayi++;
        }
      }
      $form->addButton(str_replace("%0", count($this->plugin->data->getNested("invites.".$player->getName()) ?? []), $messages->getNested("frienduioptions.mainmenu.button-invites-people")));
      $form->addButton(str_replace("%0", $sayi, $messages->getNested("frienduioptions.mainmenu.button-invites-you")));
      $form->sendToPlayer($player);
      return $form;
    }
    public function friendsMenu(Player $player) {
      $form = new SimpleForm(function (Player $player, int $data = null) {
        if($data != null) {
          if($data == 1) {
            $this->mainMenu($player);
          }
          if($data > 1) {
            $friend = $this->plugin->getArklar($player)[$data-2];
            $this->friendMenu($player,$friend);
            $this->plugin->getLogger($friend);
          }
        }
      });
      $messages = $this->plugin->messages;
      $form->setTitle($messages->getNested("frienduioptions.friends-menu.title"));
      $form->setContent($messages->getNested("frienduioptions.friends-menu.content"));
      $form->addButton($messages->getNested("frienduioptions.exit"));
      $form->addButton($messages->getNested("frienduioptions.back"));
      foreach($this->plugin->getArklar($player) as $friend) {
        $form->addButton($friend);
      }
      $form->sendToPlayer($player);
      return $form;
    }
    public function friendMenu(Player $player, string $friend) {
      $form = new SimpleForm(function (Player $player, int $data = null) use ($friend) {
        $messages = $this->plugin->messages;
        if($data != null) {
          if($data == 1) {
            $this->friendsMenu($player);
          }
          $rmv = 2;
          if($this->plugin->config->getNested("friend-tp")) {
            if($data == 2) {
              if(!$this->plugin->getServer()->getPlayer($friend)) {
                  $player->sendMessage(str_replace("%0", $this->plugin->getServer()->getPlayer($friend)->getName(), $messages->getNested("friendcommand.subcommands.teleport.error-not-online")));
                  return;
              }
              if(!in_array($this->plugin->getServer()->getPlayer($friend)->getName(), $this->plugin->getArklar($player))) {
                  $player->sendMessage(str_replace("%0", $this->plugin->getServer()->getPlayer($friend)->getName(), $messages->getNested("friendcommand.subcommands.teleport.error-not-friend")));
                  return;
              }
              $player->getPosition()->setLevel($this->plugin->getServer()->getPlayer($friend)->getLevel());
              $a = $this->plugin->getServer()->getPlayer($friend)->getPosition();
              $player->teleport(new Position($a->getX(),$a->getY(),$a->getZ(),$a->getLevel()));
              $player->sendMessage(str_replace("%0", $this->plugin->getServer()->getPlayer($friend)->getName(), $messages->getNested("friendcommand.subcommands.teleport.success1")));
              $this->plugin->getServer()->getPlayer($friend)->sendMessage(str_replace("%0", $player->getName(), $messages->getNested("friendcommand.subcommands.teleport.success2")));
            }
            $rmv = 3;
          }
          if($data == $rmv) {
            if($this->plugin->getServer()->getPlayer($friend)) {
                $friend = $this->plugin->getServer()->getPlayer($friend)->getName();
            }
            if(!in_array($friend, $this->plugin->getArklar($player))) {
                $player->sendMessage(str_replace("%0", $friend, $messages->getNested("friendcommand.subcommands.remove.error-not-friend")));
                return;
            }
            $a = [];
            foreach($this->plugin->data->get("friends") as $x) {
                if(!($x[0] == $player->getName() && $x[1] == $friend) && !($x[1] == $player->getName() && $x[0] == $friend)) {
                    array_push($a, $x);
                }
            }
            $this->plugin->data->setNested("friends", $a);
            $this->plugin->data->save();
            $this->plugin->data->reload();
            $player->sendMessage(str_replace("%0", $friend, $messages->getNested("friendcommand.subcommands.remove.success1")));
            if($this->plugin->getServer()->getPlayer($friend)) {
                $this->plugin->getServer()->getPlayer($friend)->sendMessage(str_replace("%0", $player->getName(), $messages->getNested("friendcommand.subcommands.remove.success2")));
            }
          }
        }
      });
      $messages = $this->plugin->messages;
      $form->setTitle(str_replace("%0", $friend, $messages->getNested("frienduioptions.friendmenu.title")));
      $form->setContent($messages->getNested("frienduioptions.friendmenu.content"));
      $form->addButton($messages->getNested("frienduioptions.exit"));
      $form->addButton($messages->getNested("frienduioptions.back"));
      if($this->plugin->config->getNested("friend-tp")) {
        $form->addButton($messages->getNested("frienduioptions.friendmenu.button-teleport"));
      }
      $form->addButton($messages->getNested("frienduioptions.friendmenu.button-remove"));
      $form->sendToPlayer($player);
      return $form;
    }
    public function sendRequestMenu(Player $player) {
      $form = new SimpleForm(function (Player $player, int $data = null) {
        if($data != null) {
          if($data == 1) {
            $this->mainMenu($player);
          }
          if($data > 1) {
            $liste = [];
            foreach($this->plugin->getServer()->getOnlinePlayers() as $pp) {
              if(!in_array($pp->getName(), $this->plugin->getArklar($player)) && $pp->getName() != $player->getName()) {
                array_push($liste,$pp->getName());
              }
            }
            $friend = $liste[$data-2];
            $messages = $this->plugin->messages;
            if(!$this->plugin->getServer()->getPlayer($friend)) {
                $player->sendMessage($messages->getNested("friendcommand.subcommands.invite.error-not-online"));
                return;
            }
            if(in_array($this->plugin->getServer()->getPlayer($friend)->getName(), $this->plugin->getArklar($player))) {
                $player->sendMessage($messages->getNested("friendcommand.subcommands.invite.error-already-friend"));
                return;
            }
            if($this->plugin->data->getNested("invites.".$this->plugin->getServer()->getPlayer($friend)->getName())) {
                if(in_array($player->getName(), $this->plugin->data->getNested("invites.".$this->plugin->getServer()->getPlayer($friend)->getName()))) {
                    $player->sendMessage($messages->getNested("friendcommand.subcommands.invite.error-already-invited"));
                    return;
                }
            }
            if(!$this->plugin->data->getNested("invites.".$this->plugin->getServer()->getPlayer($friend)->getName())) {
                $this->plugin->data->setNested("invites.".$this->plugin->getServer()->getPlayer($friend)->getName(), []);
                $this->plugin->data->save();
                $this->plugin->data->reload();
            }
            $a = $this->plugin->data->getNested("invites.".$this->plugin->getServer()->getPlayer($friend)->getName());
            array_push($a, $player->getName());
            $this->plugin->data->setNested("invites.".$this->plugin->getServer()->getPlayer($friend)->getName(), $a);
            $this->plugin->data->save();
            $this->plugin->data->reload();
            $player->sendMessage(str_replace("%0", $this->plugin->getServer()->getPlayer($friend)->getName(), $messages->getNested("friendcommand.subcommands.invite.success1")));
            $this->plugin->getServer()->getPlayer($friend)->sendMessage(str_replace("%0", $player->getName(), $messages->getNested("friendcommand.subcommands.invite.success2")));
          }
        }
      });
      $messages = $this->plugin->messages;
      $form->setTitle($messages->getNested("frienduioptions.send-request-menu.title"));
      $form->setContent($messages->getNested("frienduioptions.send-request-menu.content"));
      $form->addButton($messages->getNested("frienduioptions.exit"));
      $form->addButton($messages->getNested("frienduioptions.back"));
      foreach($this->plugin->getServer()->getOnlinePlayers() as $pp) {
        if(!in_array($pp->getName(), $this->plugin->getArklar($player)) && $pp->getName() != $player->getName()) {
          $form->addButton($pp->getName());
        }
      }
      $form->sendToPlayer($player);
      return $form;
    }
    public function invitesPeopleMenu(Player $player) {
      $form = new SimpleForm(function (Player $player, int $data = null) {
        if($data != null) {
          if($data == 1) {
            $this->mainMenu($player);
          }
          if($data > 1) {
            $liste = [];
            foreach($this->plugin->data->getNested("invites.".$player->getName()) as $pp) {
              array_push($liste,$pp);
            }
            $friend = $liste[$data-2];
            $this->invitePeopleSelectMenu($player,$friend);
          }
        }
      });
      $messages = $this->plugin->messages;
      $form->setTitle($messages->getNested("frienduioptions.invites-people-menu.title"));
      $form->setContent($messages->getNested("frienduioptions.invites-people-menu.content"));
      $form->addButton($messages->getNested("frienduioptions.exit"));
      $form->addButton($messages->getNested("frienduioptions.back"));
      foreach(($this->plugin->data->getNested("invites.".$player->getName()) ?? []) as $x) {
        $form->addButton($x);
      }
      $form->sendToPlayer($player);
      return $form;
    }
    public function invitePeopleSelectMenu(Player $player,$invite) {
      $form = new SimpleForm(function (Player $player, int $data = null) use ($invite) {
        if($data != null) {
          if($data == 1) {
            $this->invitesPeopleMenu($player);
          }
          $messages = $this->plugin->messages;
          if($data == 2) {
            if(!$this->plugin->getServer()->getPlayer($invite)) {
                $player->sendMessage($messages->getNested("friendcommand.subcommands.accept.error-not-online"));
                return;
            }
            if(!in_array($this->plugin->getServer()->getPlayer($invite)->getName(), $this->plugin->data->getNested("invites.".$player->getName()))) {
                $player->sendMessage($messages->getNested("friendcommand.subcommands.accept.error-not-invited"));
                return;
            }
            $yeni = [];
            foreach($this->plugin->data->getNested("invites.".$player->getName()) as $x) {
                if($x != $this->plugin->getServer()->getPlayer($invite)->getName()) {
                    array_push(($yeni ? $yeni : []), $x);
                }
            }
            $this->plugin->data->setNested("invites.".$player->getName(), $yeni);
            $yenievliler = [$player->getName(), $this->plugin->getServer()->getPlayer($invite)->getName()];
            $eskievliler = $this->plugin->data->get("friends");
            array_push($eskievliler, $yenievliler);
            $this->plugin->data->setNested("friends", $eskievliler);
            $this->plugin->data->save();
            $this->plugin->data->reload();
            $player->sendMessage(str_replace("%0", $this->plugin->getServer()->getPlayer($invite)->getName(), $messages->getNested("friendcommand.subcommands.accept.success1")));
            $this->plugin->getServer()->getPlayer($invite)->sendMessage(str_replace("%0", $player->getName(), $messages->getNested("friendcommand.subcommands.accept.success2")));
          }
          if($data == 3) {
            if(!$this->plugin->getServer()->getPlayer($invite)) {
                $player->sendMessage(str_replace("%0", $this->plugin->getServer()->getPlayer($invite)->getName(), $messages->getNested("friendcommand.subcommands.deny.error-not-online")));
                return;
            }
            if(!in_array($this->plugin->getServer()->getPlayer($invite)->getName(), $data->getNested("invites.".$player->getName()))) {
                $player->sendMessage($messages->getNested("friendcommand.subcommands.deny.error-not-invited"));
                return;
            }
            $yeni = [];
            foreach($data->getNested("invites.".$player->getName()) as $x) {
                if($x != $this->plugin->getServer()->getPlayer($invite)->getName()) {
                    array_push($yeni, $x);
                }
            }
            $data->setNested("invites.".$player->getName(), $yeni);
            $data->save();
            $data->reload();
            $player->sendMessage(str_replace("%0", $this->plugin->getServer()->getPlayer($invite)->getName(), $messages->getNested("friendcommand.subcommands.deny.success1")));
            $this->plugin->getServer()->getPlayer($invite)->sendMessage(str_replace("%0", $player->getName(), $messages->getNested("friendcommand.subcommands.deny.success2")));
          }
        }
      });
      $messages = $this->plugin->messages;
      $form->setTitle(str_replace("%0", $friend, $messages->getNested("frienduioptions.invite-people-select-menu.title")));
      $form->setContent($messages->getNested("frienduioptions.invite-people-select-menu.content"));
      $form->addButton($messages->getNested("frienduioptions.exit"));
      $form->addButton($messages->getNested("frienduioptions.back"));
      $form->addButton($messages->getNested("frienduioptions.invite-people-select-menu.button-accept"));
      $form->addButton($messages->getNested("frienduioptions.invite-people-select-menu.button-deny"));
      $form->sendToPlayer($player);
      return $form;
    }
    public function invitesYouMenu(Player $player) {
      $form = new SimpleForm(function (Player $player, int $data = null) {
        if($data != null) {
          if($data == 1) {
            $this->mainMenu($player);
          }
          if($data > 1) {
            $liste = [];
            foreach(array_keys($this->plugin->data->getNested("invites") ?? []) as $pp) {
              if($pp != $player->getName() && in_array($player->getName(),$this->plugin->data->getNested("invites")[$pp])) {
                array_push($liste,$pp);
              }
            }
            $friend = $liste[$data-2];
            $this->inviteYouSelectMenu($player,$friend);
          }
        }
      });
      $messages = $this->plugin->messages;
      $form->setTitle($messages->getNested("frienduioptions.invites-you-menu.title"));
      $form->setContent($messages->getNested("frienduioptions.invites-you-menu.content"));
      $form->addButton($messages->getNested("frienduioptions.exit"));
      $form->addButton($messages->getNested("frienduioptions.back"));
      foreach(array_keys($this->plugin->data->getNested("invites") ?? []) as $pp) {
        if($pp != $player->getName() && in_array($player->getName(),$this->plugin->data->getNested("invites")[$pp])) {
          $form->addButton($pp);
        }
      }
      $form->sendToPlayer($player);
      return $form;
    }
    public function inviteYouSelectMenu(Player $player,$invite) {
      $form = new SimpleForm(function (Player $player, int $data = null) use ($invite) {
        if($data != null) {
          if($data == 1) {
            $this->invitesYouMenu($player);
          }
          $messages = $this->plugin->messages;
          if($data == 2) {
            if(!in_array($player->getName(), $this->plugin->data->getNested("invites.".( $this->plugin->getServer()->getPlayer($invite) ? $this->plugin->getServer()->getPlayer($invite)->getName() : $invite )))) {
                $player->sendMessage($messages->getNested("friendcommand.subcommands.cancelinvite.error-not-invited"));
                return;
            }
            $yyeni = [];
            foreach($this->plugin->data->getNested("invites.".( $this->plugin->getServer()->getPlayer($invite) ? $this->plugin->getServer()->getPlayer($invite)->getName() : $invite )) as $x) {
                if($x != $player->getName()) {
                    array_push($yyeni, $x);
                }
            }
            $this->plugin->data->setNested("invites.".( $this->plugin->getServer()->getPlayer($invite) ? $this->plugin->getServer()->getPlayer($invite)->getName() : $invite ), $yyeni);
            $this->plugin->data->save();
            $this->plugin->data->reload();
            $player->sendMessage($messages->getNested("friendcommand.subcommands.cancelinvite.success"));
          }
        }
      });
      $messages = $this->plugin->messages;
      $form->setTitle(str_replace("%0", $invite, $messages->getNested("frienduioptions.invite-you-select-menu.title")));
      $form->setContent($messages->getNested("frienduioptions.invite-you-select-menu.content"));
      $form->addButton($messages->getNested("frienduioptions.exit"));
      $form->addButton($messages->getNested("frienduioptions.back"));
      $form->addButton($messages->getNested("frienduioptions.invite-you-select-menu.button-cancel"));
      $form->sendToPlayer($player);
      return $form;
    }
    public function execute(CommandSender $player, string $commandLabel, array $args) {
        $data = $this->plugin->data;
        $config = $this->plugin->config;
        $messages = $this->plugin->messages;
        $p = $this->plugin;
        if(!($player instanceof Player)) {
            $player->sendMessage($messages->getNested("friendcommand.error-use-ingame"));
            return;
        }
        if($p->config->getNested("friendui") == true && $this->plugin->formapi) {
          $this->mainMenu($player);
          return;
        }
        if(count($args) == 0) {
            $player->sendMessage($messages->getNested("friendcommand.usage"));
            return;
        }
        if($args[0] == $messages->getNested("friendcommand.subcommands.help.name") || in_array($args[0], $messages->getNested("friendcommand.subcommands.help.aliases"))) {
            $player->sendMessage($messages->getNested("friendcommand.subcommands.help.success1"));
            $player->sendMessage(str_replace("%1", $messages->getNested("friendcommand.subcommands.invite.description"), str_replace("%0", "/".strtolower($messages->getNested("friendcommand.name"))." ".$messages->getNested("friendcommand.subcommands.invite.name"), $messages->getNested("friendcommand.subcommands.help.success2"))));
            $player->sendMessage(str_replace("%1", $messages->getNested("friendcommand.subcommands.list.description"), str_replace("%0", "/".strtolower($messages->getNested("friendcommand.name"))." ".$messages->getNested("friendcommand.subcommands.list.name"), $messages->getNested("friendcommand.subcommands.help.success2"))));
            $player->sendMessage(str_replace("%1", $messages->getNested("friendcommand.subcommands.remove.description"), str_replace("%0", "/".strtolower($messages->getNested("friendcommand.name"))." ".$messages->getNested("friendcommand.subcommands.remove.name"), $messages->getNested("friendcommand.subcommands.help.success2"))));
            $player->sendMessage(str_replace("%1", $messages->getNested("friendcommand.subcommands.accept.description"), str_replace("%0", "/".strtolower($messages->getNested("friendcommand.name"))." ".$messages->getNested("friendcommand.subcommands.accept.name"), $messages->getNested("friendcommand.subcommands.help.success2"))));
            $player->sendMessage(str_replace("%1", $messages->getNested("friendcommand.subcommands.deny.description"), str_replace("%0", "/".strtolower($messages->getNested("friendcommand.name"))." ".$messages->getNested("friendcommand.subcommands.deny.name"), $messages->getNested("friendcommand.subcommands.help.success2"))));
            $player->sendMessage(str_replace("%1", $messages->getNested("friendcommand.subcommands.chat.description"), str_replace("%0", "/".strtolower($messages->getNested("friendcommand.name"))." ".$messages->getNested("friendcommand.subcommands.chat.name"), $messages->getNested("friendcommand.subcommands.help.success2"))));
            $player->sendMessage(str_replace("%1", $messages->getNested("friendcommand.subcommands.cancelinvite.description"), str_replace("%0", "/".strtolower($messages->getNested("friendcommand.name"))." ".$messages->getNested("friendcommand.subcommands.cancelinvite.name"), $messages->getNested("friendcommand.subcommands.help.success2"))));
            $player->sendMessage(str_replace("%1", $messages->getNested("friendcommand.subcommands.invites.description"), str_replace("%0", "/".strtolower($messages->getNested("friendcommand.name"))." ".$messages->getNested("friendcommand.subcommands.invites.name"), $messages->getNested("friendcommand.subcommands.help.success2"))));
            if($config->get("friend-tp") == true) {
                $player->sendMessage(str_replace("%1", $messages->getNested("friendcommand.subcommands.teleport.description"), str_replace("%0", "/".strtolower($messages->getNested("friendcommand.name"))." ".$messages->getNested("friendcommand.subcommands.teleport.name"), $messages->getNested("friendcommand.subcommands.help.success2"))));
            }
        } else if($args[0] == $messages->getNested("friendcommand.subcommands.invite.name") || in_array($args[0], $messages->getNested("friendcommand.subcommands.invite.aliases"))) {
            if(count($args) != 2 || !$p->getServer()->getPlayer($args[1])) {
                $player->sendMessage($messages->getNested("friendcommand.subcommands.invite.usage"));
                return;
            }
            if(!$p->getServer()->getPlayer($args[1])) {
                $player->sendMessage($messages->getNested("friendcommand.subcommands.invite.error-not-online"));
                return;
            }
            if(in_array($p->getServer()->getPlayer($args[1])->getName(), $p->getArklar($player))) {
                $player->sendMessage($messages->getNested("friendcommand.subcommands.invite.error-already-friend"));
                return;
            }
            if($data->getNested("invites.".$p->getServer()->getPlayer($args[1])->getName())) {
                if(in_array($player->getName(), $data->getNested("invites.".$p->getServer()->getPlayer($args[1])->getName()))) {
                    $player->sendMessage($messages->getNested("friendcommand.subcommands.invite.error-already-invited"));
                    return;
                }
            }
            if(!$data->getNested("invites.".$p->getServer()->getPlayer($args[1])->getName())) {
                $data->setNested("invites.".$p->getServer()->getPlayer($args[1])->getName(), []);
                $data->save();
                $data->reload();
            }
            $a = $data->getNested("invites.".$p->getServer()->getPlayer($args[1])->getName());
            array_push($a, $player->getName());
            $data->setNested("invites.".$p->getServer()->getPlayer($args[1])->getName(), $a);
            $data->save();
            $data->reload();
            $player->sendMessage(str_replace("%0", $p->getServer()->getPlayer($args[1])->getName(), $messages->getNested("friendcommand.subcommands.invite.success1")));
            $p->getServer()->getPlayer($args[1])->sendMessage(str_replace("%0", $player->getName(), $messages->getNested("friendcommand.subcommands.invite.success2")));
        } else if($args[0] == $messages->getNested("friendcommand.subcommands.list.name") || in_array($args[0], $messages->getNested("friendcommand.subcommands.list.aliases"))) {
            if(count($p->getArklar($player)) == 0) {
                $player->sendMessage($messages->getNested("friendcommand.subcommands.list.error-no-friends"));
                return;
            } else {
                $str = "";
                foreach($p->getArklar($player) as $x) {
                    if(array_search($x, $p->getArklar($player)) == count($p->getArklar($player))-1) {
                        $str = $str . $x;
                    } else {
                        $str = $str . $x . ", ";
                    }
                }
                $player->sendMessage(str_replace("%0", $str, $messages->getNested("friendcommand.subcommands.list.success")));
            }
        } else if($args[0] == $messages->getNested("friendcommand.subcommands.remove.name") || in_array($args[0], $messages->getNested("friendcommand.subcommands.remove.aliases"))) {
            if(count($args) != 2) {
                $player->sendMessage($messages->getNested("friendcommand.subcommands.remove.usage"));
                return;
            }
            if($p->getServer()->getPlayer($args[1])) {
                $args[1] = $p->getServer()->getPlayer($args[1])->getName();
            }
            if(!in_array($args[1], $p->getArklar($player))) {
                $player->sendMessage(str_replace("%0", $args[1], $messages->getNested("friendcommand.subcommands.remove.error-not-friend")));
                return;
            }
            $a = [];
            foreach($data->get("friends") as $x) {
                if(!($x[0] == $player->getName() && $x[1] == $args[1]) && !($x[1] == $player->getName() && $x[0] == $args[1])) {
                    array_push($a, $x);
                }
            }
            $data->setNested("friends", $a);
            $data->save();
            $data->reload();
            $player->sendMessage(str_replace("%0", $args[1], $messages->getNested("friendcommand.subcommands.remove.success1")));
            if($p->getServer()->getPlayer($args[1])) {
                $p->getServer()->getPlayer($args[1])->sendMessage(str_replace("%0", $player->getName(), $messages->getNested("friendcommand.subcommands.remove.success2")));
            }
        } else if($args[0] == $messages->getNested("friendcommand.subcommands.accept.name") || in_array($args[0], $messages->getNested("friendcommand.subcommands.accept.aliases"))) {
            if(count($args) != 2 || !$p->getServer()->getPlayer($args[1])) {
                $player->sendMessage($messages->getNested("friendcommand.subcommands.accept.usage"));
                return;
            }
            if(!$p->getServer()->getPlayer($args[1])) {
                $player->sendMessage(str_replace("%0", $p->getServer()->getPlayer($args[1])->getName(), $messages->getNested("friendcommand.subcommands.accept.error-not-online")));
                return;
            }
            if(!in_array($p->getServer()->getPlayer($args[1])->getName(), $data->getNested("invites.".$player->getName()))) {
                $player->sendMessage($messages->getNested("friendcommand.subcommands.accept.error-not-invited"));
                return;
            }
            $yeni = [];
            foreach($data->getNested("invites.".$player->getName()) as $x) {
                if($x != $p->getServer()->getPlayer($args[1])->getName()) {
                    array_push(($yeni ? $yeni : []), $x);
                }
            }
            $data->setNested("invites.".$player->getName(), $yeni);
            $yenievliler = [$player->getName(), $p->getServer()->getPlayer($args[1])->getName()];
            $eskievliler = $data->get("friends");
            array_push($eskievliler, $yenievliler);
            $data->setNested("friends", $eskievliler);
            $data->save();
            $data->reload();
            $player->sendMessage(str_replace("%0", $p->getServer()->getPlayer($args[1])->getName(), $messages->getNested("friendcommand.subcommands.accept.success1")));
            $p->getServer()->getPlayer($args[1])->sendMessage(str_replace("%0", $player->getName(), $messages->getNested("friendcommand.subcommands.accept.success2")));
        } else if($args[0] == $messages->getNested("friendcommand.subcommands.deny.name") || in_array($args[0], $messages->getNested("friendcommand.subcommands.deny.aliases"))) {
            if(count($args) != 2 || !$p->getServer()->getPlayer($args[1])) {
                $player->sendMessage($messages->getNested("friendcommand.subcommands.deny.usage"));
                return;
            }
            if(!$p->getServer()->getPlayer($args[1])) {
                $player->sendMessage(str_replace("%0", $p->getServer()->getPlayer($args[1])->getName(), $messages->getNested("friendcommand.subcommands.deny.error-not-online")));
                return;
            }
            if(!in_array($p->getServer()->getPlayer($args[1])->getName(), $data->getNested("invites.".$player->getName()))) {
                $player->sendMessage($messages->getNested("friendcommand.subcommands.deny.error-not-invited"));
                return;
            }
            $yeni = [];
            foreach($data->getNested("invites.".$player->getName()) as $x) {
                if($x != $p->getServer()->getPlayer($args[1])->getName()) {
                    array_push($yeni, $x);
                }
            }
            $data->setNested("invites.".$player->getName(), $yeni);
            $data->save();
            $data->reload();
            $player->sendMessage(str_replace("%0", $p->getServer()->getPlayer($args[1])->getName(), $messages->getNested("friendcommand.subcommands.deny.success1")));
            $p->getServer()->getPlayer($args[1])->sendMessage(str_replace("%0", $player->getName(), $messages->getNested("friendcommand.subcommands.deny.success2")));
        } else if($args[0] == $messages->getNested("friendcommand.subcommands.chat.name") || in_array($args[0], $messages->getNested("friendcommand.subcommands.chat.aliases"))) {
            if(count($args) < 3 || !$p->getServer()->getPlayer($args[1])) {
                $player->sendMessage($messages->getNested("friendcommand.subcommands.chat.usage"));
                return;
            }
            if(!$p->getServer()->getPlayer($args[1])) {
                $player->sendMessage(str_replace("%0", $p->getServer()->getPlayer($args[1])->getName(), $messages->getNested("friendcommand.subcommands.chat.error-not-online")));
                return;
            }
            if(!in_array($p->getServer()->getPlayer($args[1])->getName(), $p->getArklar($player))) {
                $player->sendMessage(str_replace("%0", $p->getServer()->getPlayer($args[1])->getName(), $messages->getNested("friendcommand.subcommands.chat.error-not-friend")));
                return;
            }
            $params = "";
            for($i = 2;$i<count($args);$i=$i+1) {
                $params = $params . ($i != 2 ? " " : "") . $args[$i];
            }
            $player->sendMessage(str_replace(["%0", "%1"], [$p->getServer()->getPlayer($args[1])->getName(), $params], $messages->getNested("friendcommand.subcommands.chat.success1")));
            $p->getServer()->getPlayer($args[1])->sendMessage(str_replace(["%0", "%1"], [$player->getName(), $params], $messages->getNested("friendcommand.subcommands.chat.success2")));
        } else if(($args[0] == $messages->getNested("friendcommand.subcommands.teleport.name") || in_array($args[0], $messages->getNested("friendcommand.subcommands.teleport.aliases"))) && $config->get("friend-tp") == true) {
            if(count($args) != 2 || !$p->getServer()->getPlayer($args[1])) {
                $player->sendMessage($messages->getNested("friendcommand.subcommands.teleport.usage"));
                return;
            }
            if(!$p->getServer()->getPlayer($args[1])) {
                $player->sendMessage(str_replace("%0", $p->getServer()->getPlayer($args[1])->getName(), $messages->getNested("friendcommand.subcommands.teleport.error-not-online")));
                return;
            }
            if(!in_array($p->getServer()->getPlayer($args[1])->getName(), $p->getArklar($player))) {
                $player->sendMessage(str_replace("%0", $p->getServer()->getPlayer($args[1])->getName(), $messages->getNested("friendcommand.subcommands.teleport.error-not-friend")));
                return;
            }
            $player->getPosition()->setLevel($p->getServer()->getPlayer($args[1])->getLevel());
            $a = $p->getServer()->getPlayer($args[1])->getPosition();
            $player->teleport(new Position($a->getX(),$a->getY(),$a->getZ(),$a->getLevel()));
            $player->sendMessage(str_replace("%0", $p->getServer()->getPlayer($args[1])->getName(), $messages->getNested("friendcommand.subcommands.teleport.success1")));
            $p->getServer()->getPlayer($args[1])->sendMessage(str_replace("%0", $player->getName(), $messages->getNested("friendcommand.subcommands.teleport.success2")));
        } else if(($args[0] == $messages->getNested("friendcommand.subcommands.cancelinvite.name") || in_array($args[0], $messages->getNested("friendcommand.subcommands.cancelinvite.aliases")))) {
            if(count($args) != 2 || !$p->getServer()->getPlayer($args[1])) {
                $player->sendMessage($messages->getNested("friendcommand.subcommands.cancelinvite.usage"));
                return;
            }
            if(!in_array($player->getName(), $data->getNested("invites.".$p->getServer()->getPlayer($args[1])->getName()))) {
                $player->sendMessage($messages->getNested("friendcommand.subcommands.cancelinvite.error-not-invited"));
                return;
            }
            $yyeni = [];
            foreach($data->getNested("invites.".$p->getServer()->getPlayer($args[1])->getName()) as $x) {
                if($x != $player->getName()) {
                    array_push($yyeni, $x);
                }
            }
            $data->setNested("invites.".$p->getServer()->getPlayer($args[1])->getName(), $yyeni);
            $data->save();
            $data->reload();
            $player->sendMessage($messages->getNested("friendcommand.subcommands.cancelinvite.success"));
        } else if(($args[0] == $messages->getNested("friendcommand.subcommands.invites.name") || in_array($args[0], $messages->getNested("friendcommand.subcommands.invites.aliases")))) {
            if(!$data->getNested("invites.".$player->getName()) || count($data->getNested("invites.".$player->getName())) < 1) {
                $player->sendMessage($messages->getNested("friendcommand.subcommands.invites.error-not-invited"));
                return;
            } else {
                $str = "";
                foreach($data->getNested("invites.".$player->getName()) as $x) {
                    if($str == "") {
                        $str = $x;
                    } else {
                        $str = $str . ", " . $x;
                    }
                }
                $player->sendMessage(str_replace("%0", $str, $messages->getNested("friendcommand.subcommands.invites.success")));
            }
        } else {
            $player->sendMessage($messages->getNested("friendcommand.usage"));
            return;
        }
    }
    public function getPlugin(): Plugin {
        return $this->plugin;
    }

}
