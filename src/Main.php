<?php

namespace lokiPM\ReportSystem;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use jojoe77777\FormAPI\SimpleForm;

class Main extends PluginBase {

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "report" && $sender instanceof Player) {
            $this->openReportForm($sender);
            return true;
        }
        return false;
    }

    public function openReportForm(Player $player): void {
        $form = new SimpleForm(function (Player $player, ?int $data) {});
        $form->setTitle("Report a Player");
        $form->setContent("Soon");
        $player->sendForm($form);
    }
}
