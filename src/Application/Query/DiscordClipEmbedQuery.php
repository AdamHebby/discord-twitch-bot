<?php

namespace DiscordTwitchBot\Application\Query;

use Discord\Discord;
use Discord\Parts\Embed\Embed;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Application\EmbeddedMessage;
use DiscordTwitchBot\Model\TwitchClip;
use DiscordTwitchBot\Repository\GameRepository;

class DiscordClipEmbedQuery
{
    public function __construct(
        private readonly Discord $discord,
        private readonly GameRepository $gameRepository,
    ) {
    }

    public function generate(
        TwitchClip $clip,
    ): EmbeddedMessage {
        Benchmarker::getInstance()->start(__CLASS__);

        $gameName  = $this->gameRepository->get($clip->gameId);
        $gameField = $gameName ? ['name' => 'Game', 'value' => $gameName] : null;

        $embed = new Embed($this->discord, [
            'title'  => $clip->title,
            'url'    => $clip->getUrl(),
            'image'  => ['url' => $clip->thumbnailUrl],
            'author' => [
                'name' => "New clip for {$clip->broadcasterName}. Clipped by {$clip->creatorName}",
                'url'  => $clip->getUrl(),
            ],
            'color'  => hexdec('6a44bc'),
            'timestamp' => $clip->createdAt->format('c'),
            'fields' => array_filter([
                [
                    'name'  => 'Views',
                    'value' => $clip->viewCount,
                    'inline' => true,
                ],
                [
                    'name'  => 'Duration',
                    'value' => $clip->duration . 's',
                    'inline' => true,
                ],
                $gameField,
            ]),
            'footer' => [
                'text' => 'Made by .justabignerd',
            ]
        ]);

        Benchmarker::getInstance()->stop(__CLASS__);

        return new EmbeddedMessage("", $embed);
    }
}
