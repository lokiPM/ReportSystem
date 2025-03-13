<?php

namespace lokiPM\ReportSystem;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\form\Form;
use pocketmine\form\ModalForm;

class Main extends PluginBase {

    public function onEnable(): void {
        $this->getLogger()->info("ReportSystem wurde aktiviert!");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "report") {
            if ($sender instanceof Player) {
                $this->openReportForm($sender);
            } else {
                $sender->sendMessage("Dieser Befehl kann nur im Spiel verwendet werden.");
            }
            return true;
        }
        return false;
    }

    public function openReportForm(Player $player): void {
        $form = new ModalForm(
            "Report a Player",
            "Soon",
            function (Player $player, bool $data): void {}
        );
        $player->sendForm($form);
    }
}
