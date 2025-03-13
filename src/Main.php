<?php

namespace lokiPM\ReportSystem;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\utils\Config;

class Main extends PluginBase {

    private $config;

    public function onEnable(): void {
        $this->saveResource("config.yml");
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
    }

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

            $selectedPlayerIndex = $data[0];
            $reason = $data[1];

            if ($selectedPlayerIndex === null || trim($reason) === "") {
                $this->openReportForm($player, "Please fill every fields!");
                return;
            }

            $onlinePlayers = array_values(array_map(function (Player $player) {
                return $player->getName();
            }, $this->getServer()->getOnlinePlayers()));

            $selectedPlayer = $onlinePlayers[$selectedPlayerIndex];

            $this->sendDiscordWebhook($player->getName(), $selectedPlayer, $reason);
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

    public function sendDiscordWebhook(string $reporter, string $reportedPlayer, string $reason): void {
        $webhookUrl = $this->config->get("webhook-url");

        if (empty($webhookUrl)) {
            return;
        }

        $embed = [
            "title" => "Report by $reporter",
            "description" => "Player: $reportedPlayer\nReason: $reason",
            "color" => 16711680, // Hellrot
        ];

        $data = [
            "embeds" => [$embed],
        ];

        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // SSL-Zertifikatsprüfung deaktivieren
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // SSL-Zertifikatsprüfung deaktivieren

        $response = curl_exec($ch);
        if ($response === false) {
            $this->getLogger()->error("Fehler beim Senden des Webhooks: " . curl_error($ch));
        }
        curl_close($ch);
    }
}
