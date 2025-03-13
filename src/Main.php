<?php

namespace lokiPM\ReportSystem;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use jojoe77777\FormAPI\CustomForm;

class Main extends PluginBase {

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "report" && $sender instanceof Player) {
            $this->openReportForm($sender);
            return true;
        }
        return false;
    }

    public function openReportForm(Player $player, ?string $error = null): void {
        $form = new CustomForm(function (Player $player, ?array $data) {
            if ($data === null) return; // Formular geschlossen

            $selectedPlayer = $data[0];
            $reason = $data[1];

            if ($selectedPlayer === null || trim($reason) === "") {
                $this->openReportForm($player, "Please fill every fields!");
                return;
            }
        });

        $form->setTitle("Report a Player");

        $onlinePlayers = [];
        foreach ($this->getServer()->getOnlinePlayers() as $onlinePlayer) {
            $onlinePlayers[] = $onlinePlayer->getName();
        }

        $form->addDropdown("Select a Player", $onlinePlayers);
        $form->addInput("Reason", "Type in Reason");

        if ($error !== null) {
            $form->addLabel($error);
        }

        $player->sendForm($form);
    }
}
