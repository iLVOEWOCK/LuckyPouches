<?php

namespace wockkinmycup\LuckyPouches\tasks;

use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\api\legacy\ClosureContext;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat as C;
use wockkinmycup\LuckyPouches\Loader;

class TitleRevealTask extends Task {

    private Player $player;
    private int $money;
    private int $revealedPosition = 0;

    public function __construct(Player $player, int $money) {
        $this->player = $player;
        $this->money = $money;
    }

    public function onRun(): void {
        if ($this->revealedPosition >= mb_strlen((string) $this->money)) {
            $this->getHandler()->cancel();
            $revealedTitle = C::colorize(Loader::getInstance()->getConfig()->get("animations.title.title"));
            $openingSubtitle = Loader::getInstance()->getConfig()->get("animations.title.subtitle");

            $this->player->sendTitle($revealedTitle, C::colorize($openingSubtitle), 1, 2);

            BedrockEconomyAPI::legacy()->addToPlayerBalance(
                $this->player->getName(),
                $this->money,
                ClosureContext::create(
                    function (bool $wasUpdated) {
                        // Handle callback if needed
                    }
                )
            );

            return;
        }

        $this->revealedPosition++;
        $moneyString = (string) $this->money;
        $revealedMoney = mb_substr($moneyString, 0, $this->revealedPosition);
        $obfuscatedMoney = $this->obfuscateAmount(mb_substr($moneyString, $this->revealedPosition));

        $revealColor = Loader::getInstance()->getConfig()->get("animations.title.reveal_color");
        $obfuscateColor = Loader::getInstance()->getConfig()->get("animations.title.obfuscate_color");

        $revealedTitle = C::colorize($revealColor) . "$" . C::colorize($revealColor) . $revealedMoney . C::colorize($obfuscateColor) . $obfuscatedMoney;
        $openingSubtitle = Loader::getInstance()->getConfig()->get("animations.title.subtitle");

        $this->player->sendTitle(C::colorize($revealedTitle), C::colorize($openingSubtitle), 0, 1);
    }

    private function obfuscateAmount($amount): string {
        $obfuscated = "";
        $obfuscateColor = Loader::getInstance()->getConfig()->get("animations.title.obfuscate_color");
        foreach (mb_str_split($amount) as $char) {
            $obfuscated .= C::colorize($obfuscateColor) . "§k$char";
        }
        return $obfuscated;
    }

}
