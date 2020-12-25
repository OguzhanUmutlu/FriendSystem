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
class FriendCommand extends Command implements PluginIdentifiableCommand {
    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        parent::__construct($plugin->messages->getNested("friendcommand.name"), $plugin->messages->getNested("friendcommand.description"), null, $plugin->messages->getNested("friendcommand.aliases"));
        if($plugin->messages->getNested("friendcommand.permission")) {
            $this->setPermission($plugin->messages->getNested("friendcommand.permission"));
        }
    }
    public function execute(CommandSender $player, string $commandLabel, array $args) {
        $data = $this->plugin->data;
        $config = $this->plugin->config;
        $messages = $this->plugin->messages;
        $p = $this->plugin;
        // $messages->getNested("friendcommand.sss")
        if(!($player instanceof Player)) {
            $player->sendMessage($messages->getNested("friendcommand.error-use-ingame"));
            return;
        }
        if(count($args) == 0) {
            $player->sendMessage($messages->getNested("friendcommand.usage"));
            return;
        }
        if($args[0] == $messages->getNested("friendcommand.subcommands.help.name") || in_array($args[0], $messages->getNested("friendcommand.subcommands.help.aliases"))) {
            $player->sendMessage($messages->getNested("friendcommand.subcommands.help.success1"));
            $player->sendMessage(str_replace("%1", $messages->getNested("friendcommand.subcommands.invite.description"), str_replace("%0", "/".$messages->getNested("friendcommand.name")." ".$messages->getNested("friendcommand.subcommands.invite.name"), $messages->getNested("friendcommand.subcommands.help.success2"))));
            $player->sendMessage(str_replace("%1", $messages->getNested("friendcommand.subcommands.list.description"), str_replace("%0", "/".$messages->getNested("friendcommand.name")." ".$messages->getNested("friendcommand.subcommands.list.name"), $messages->getNested("friendcommand.subcommands.help.success2"))));
            $player->sendMessage(str_replace("%1", $messages->getNested("friendcommand.subcommands.remove.description"), str_replace("%0", "/".$messages->getNested("friendcommand.name")." ".$messages->getNested("friendcommand.subcommands.remove.name"), $messages->getNested("friendcommand.subcommands.help.success2"))));
            $player->sendMessage(str_replace("%1", $messages->getNested("friendcommand.subcommands.accept.description"), str_replace("%0", "/".$messages->getNested("friendcommand.name")." ".$messages->getNested("friendcommand.subcommands.accept.name"), $messages->getNested("friendcommand.subcommands.help.success2"))));
            $player->sendMessage(str_replace("%1", $messages->getNested("friendcommand.subcommands.deny.description"), str_replace("%0", "/".$messages->getNested("friendcommand.name")." ".$messages->getNested("friendcommand.subcommands.deny.name"), $messages->getNested("friendcommand.subcommands.help.success2"))));
            $player->sendMessage(str_replace("%1", $messages->getNested("friendcommand.subcommands.chat.description"), str_replace("%0", "/".$messages->getNested("friendcommand.name")." ".$messages->getNested("friendcommand.subcommands.chat.name"), $messages->getNested("friendcommand.subcommands.help.success2"))));
            $player->sendMessage(str_replace("%1", $messages->getNested("friendcommand.subcommands.cancelinvite.description"), str_replace("%0", "/".$messages->getNested("friendcommand.name")." ".$messages->getNested("friendcommand.subcommands.cancelinvite.name"), $messages->getNested("friendcommand.subcommands.help.success2"))));
            $player->sendMessage(str_replace("%1", $messages->getNested("friendcommand.subcommands.invites.description"), str_replace("%0", "/".$messages->getNested("friendcommand.name")." ".$messages->getNested("friendcommand.subcommands.invites.name"), $messages->getNested("friendcommand.subcommands.help.success2"))));
            if($config->get("friend-tp") == true) {
                $player->sendMessage(str_replace("%1", $messages->getNested("friendcommand.subcommands.teleport.description"), str_replace("%0", "/".$messages->getNested("friendcommand.name")." ".$messages->getNested("friendcommand.subcommands.teleport.name"), $messages->getNested("friendcommand.subcommands.help.success2"))));
            }
        } else if($args[0] == $messages->getNested("friendcommand.subcommands.invite.name") || in_array($args[0], $messages->getNested("friendcommand.subcommands.invite.aliases"))) {
            if(count($args) != 2 || !$p->getServer()->getPlayer($args[1])) {
                $player->sendMessage($messages->getNested("friendcommand.subcommands.invite.usage"));
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
                    array_push(($a ? $a : []), $x);
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
            if(!in_array($p->getServer()->getPlayer($args[1])->getName(), $data->getNested("invites.".$player->getName()))) {
                $player->sendMessage($messages->getNested("friendcommand.subcommands.accept.error-not-invited"));
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
            if(!in_array($p->getServer()->getPlayer($args[1])->getName(), $p->getArklar($player))) {
                $player->sendMessage(str_replace("%0", $p->getServer()->getPlayer($args[1])->getName(), $messages->getNested("friendcommand.subcommands.teleport.error-not-friend")));
                return;
            }
            $player->getPosition()->setLevel($p->getServer()->getPlayer($args[1])->getLevel());
            $a = $p->getServer()->getPlayer($args[1])->getPosition();
            $player->teleport(new Vector3($a->getX(),$a->getY(),$a->getZ()));
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
