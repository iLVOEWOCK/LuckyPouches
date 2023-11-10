<?php

namespace wockkinmycup\LuckyPouches\listeners;

use Exception;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use wockkinmycup\LuckyPouches\Loader;
use wockkinmycup\LuckyPouches\tasks\TitleRevealTask;
use pocketmine\utils\TextFormat as C;
use wockkinmycup\LuckyPouches\utils\Utils;

class PouchesListener implements Listener
{

    public array $pouchCooldown = [];

    public function onPlace(BlockPlaceEvent $ev)
    {
        $i = $ev->getItem();
        $t = $i->getNamedTag();

        if ($t->getTag("luckypouches")) {
            $ev->cancel();
        }
    }

    /**
     * @throws Exception
     */
    public function onUse(PlayerItemUseEvent $event): bool
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $tag = $item->getNamedTag();
        $config = new Config(Loader::getInstance()->getDataFolder() . "config.yml", Config::YAML);
        $messages = new Config(Loader::getInstance()->getDataFolder() . "messages.yml", Config::YAML);
        $currentTime = time();

        if (isset($this->pouchCooldown[$player->getName()])) {
            $remainingCooldown = $this->pouchCooldown[$player->getName()] - $currentTime;

            if ($remainingCooldown > 0) {
                $player->sendMessage(C::RED . C::BOLD . "[!]" . C::RESET . C::GRAY . " Please wait $remainingCooldown seconds before opening another pouch.");
                return false;
            }
        }
        if ($tag->getTag("luckypouches")) {
            $pouchTag = $tag->getString("luckypouches");
            $configArray = $config->getAll();
            if (!isset($configArray["pouches"][$pouchTag])) {
                throw new Exception("Invalid pouch identifier: $pouchTag");
            }
            $pouchData = $configArray["pouches"][$pouchTag];

            $animation = isset($pouchData["animation"]) ? strtoupper($pouchData["animation"]) : "NONE";

            switch ($animation) {
                case "TITLE":
                    $this->pouchCooldown[$player->getName()] = $currentTime + $config->get("animations.title.cooldown");
                    Loader::getInstance()->getScheduler()->scheduleDelayedTask(
                        new ClosureTask(function () use ($player) {
                            unset($this->pouchCooldown[$player->getName()]);
                        }),
                        $config->get("animations.title.cooldown") * 20
                    );
                    $item->setCount($item->getCount() - 1);
                    $player->getInventory()->setItemInHand($item);
                    $minAmount = $pouchData["min"];
                    $maxAmount = $pouchData["max"];
                    $money = mt_rand($minAmount, $maxAmount);
                    //$obfuscatedTitle = "§k" . str_repeat("#", strlen((string)$money));
                    //$player->sendTitle($obfuscatedTitle, C::colorize($config->get("animations.title.subtitle")), 1, 2);
                    $task = new TitleRevealTask($player, $money);
                    Loader::getInstance()->getScheduler()->scheduleRepeatingTask($task, 10);
                    if ($config->get("animations.title.show_prize_message") === true) {
                        $msg = $messages->get("prize_message");
                        $msg = str_replace(["{prefix}", "{prize}"], [$messages->get("prefix"), $money],  $msg);
                        $player->sendMessage(C::colorize($msg));
                    }
                    break;

                case "ACTIONBAR":
                    // Handle ACTIONBAR animation here
                    // You can add code for ACTIONBAR animation
                    break;

                case "BOSSBAR":
                    // Handle BOSSBAR animation here
                    // You can add code for BOSSBAR animation
                    break;

                case "RANDOM":
                    // Handle RANDOM animation here
                    // You can add code for RANDOM animation
                    break;

                case "NONE":
                    $this->pouchCooldown[$player->getName()] = $currentTime + $config->get("animations.none.cooldown");
                    $item->setCount($item->getCount() - 1);
                    $player->getInventory()->setItemInHand($item);
                    $minAmount = $pouchData["min"];
                    $maxAmount = $pouchData["max"];
                    $money = mt_rand($minAmount, $maxAmount);
                    $prize_msg = $messages->get("prize_message");
                    $prize_msg = str_replace(["{prefix}", "{prize}"], [$messages->get("prefix"), $money], $prize_msg);
                    $player->sendMessage(C::colorize($prize_msg));
                    break;

                default:
                    $player->sendMessage(C::RED . C::BOLD . "[!]" . C::RESET . C::GRAY . " Invalid animation type: $animation");
            }
        }
        return true;
    }
}
