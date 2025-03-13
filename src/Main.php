<?php

namespace lokiPM\ReportSystem;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\utils\Config;
use pocketmine\scheduler\Task;

class Main extends PluginBase {

    private $config;
    private $blacklist = [];
    private $blacklistConfig;

    public function onEnable(): void {
        $this->saveResource("config.yml");
        $this->saveResource("blacklist.yml");
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->blacklistConfig = new Config($this->getDataFolder() . "blacklist.yml", Config::YAML);

        $this->updateBlacklist();
        $this->getScheduler()->scheduleRepeatingTask(new class($this) extends Task {
            private $plugin;

            public function __construct(Main $plugin) {
                $this->plugin = $plugin;
            }

            public function onRun(): void {
                $this->plugin->updateBlacklist();
            }
        }, 20);
    }

    public function updateBlacklist(): void {
        $this->blacklist = array_map('strtolower', $this->blacklistConfig->get("players", []));
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "report" && $sender instanceof Player) {
            $playerName = strtolower($sender->getName());
            if (in_array($playerName, $this->blacklist)) {
                $sender->sendMessage("§cYou are blacklisted.");
                return true;
            }
            $this->openReportForm($sender);
            return true;
        }
        return false;
    }

    public function openReportForm(Player $player): void {
        $form = new CustomForm(function (Player $player, ?array $data) {
            if ($data === null) return;

            $selectedPlayerIndex = $data[0];
            $reason = $data[1];
            $clipUrl = $data[2];

            if ($selectedPlayerIndex === null || trim($reason) === "" || trim($clipUrl) === "") {
                $this->openReportForm($player);
                return;
            }

            $onlinePlayers = array_values(array_map(function (Player $player) {
                return $player->getName();
            }, $this->getServer()->getOnlinePlayers()));

            $selectedPlayer = $onlinePlayers[$selectedPlayerIndex];

            $this->sendDiscordWebhook($player->getName(), $selectedPlayer, $reason, $clipUrl);
            $player->sendMessage("§aYour Report was sent.");
        });

        $form->setTitle("Report a Player");

        $onlinePlayers = [];
        foreach ($this->getServer()->getOnlinePlayers() as $onlinePlayer) {
            $onlinePlayers[] = $onlinePlayer->getName();
        }

        $form->addDropdown("Select a Player", $onlinePlayers);
        $form->addInput("Reason", "Type in Reason");
        $form->addInput("Clip URL", "Paste Clip URL here");

        $player->sendForm($form);
    }

    public function sendDiscordWebhook(string $reporter, string $reportedPlayer, string $reason, string $clipUrl): void {
        $webhookUrl = $this->config->get("webhook-url");

        if (empty($webhookUrl)) {
            return;
        }

        $description = "Player: $reportedPlayer\nReason: $reason\nClip URL: $clipUrl";

        $embed = [
            "title" => "Report by $reporter",
            "description" => $description,
            "color" => 16711680,
        ];

        $data = [
            "embeds" => [$embed],
        ];

        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        curl_close($ch);
    }
}
