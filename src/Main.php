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
    public $blacklist = [];
    public $blacklistConfig;

    public function onEnable(): void {
        $this->saveResource("config.yml");
        $this->saveResource("blacklist.yml");
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->blacklistConfig = new Config($this->getDataFolder() . "blacklist.yml", Config::YAML);
        $this->blacklist = array_map('strtolower', $this->blacklistConfig->get("players", []));
        $this->getScheduler()->scheduleRepeatingTask(new class($this) extends Task {
            private $plugin;
            public function __construct(Main $plugin) {
                $this->plugin = $plugin;
            }
            public function onRun(): void {
                $this->plugin->blacklist = array_map('strtolower', $this->plugin->blacklistConfig->get("players", []));
            }
        }, 1);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "report") {
            if ($sender instanceof Player) {
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

        if ($command->getName() === "reportreload") {
            if (!$sender->hasPermission("report.reload")) {
                return true;
            }
            $this->reloadPlugin();
            $sender->sendMessage("§aDone!");
            return true;
        }

        return false;
    }

    public function openReportForm(Player $player): void {
        $form = new CustomForm(function (Player $player, ?array $data) {
            if ($data === null) {
                return;
            }

            $selectedPlayerIndex = $data[2];
            $reason = $data[3];
            $clipUrl = $data[4];

            if (trim($reason) === "" || trim($clipUrl) === "") {
                $this->openReportForm($player);
                return;
            }

            $onlinePlayers = array_values(array_map(function (Player $player) {
                return $player->getName();
            }, $this->getServer()->getOnlinePlayers()));

            $selectedPlayer = $onlinePlayers[$selectedPlayerIndex];
            $this->sendDiscordWebhook($player->getName(), $selectedPlayer, $reason, $clipUrl);
            $player->sendMessage("§aDone! Your report has been sent.");
        });

        $form->setTitle("Report a Player");

        $form->addLabel("§cUse this Website for Clips:");
        $form->addLabel("§ehttps://jumpshare.com/file-sharing/video");

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

        $embed = [
            "title" => "Report by $reporter",
            "description" => "Player: $reportedPlayer\nReason: $reason\nClip URL: $clipUrl",
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

    public function reloadPlugin(): void {
        $this->reloadConfig();
        $this->blacklistConfig->reload();
        $this->blacklist = array_map('strtolower', $this->blacklistConfig->get("players", []));
    }
}