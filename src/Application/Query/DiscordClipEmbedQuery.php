<?php

namespace DiscordTwitchBot\Application\Query;

use Discord\Discord;
use Discord\Parts\Embed\Embed;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Application\EmbeddedMessage;
use DiscordTwitchBot\Model\TwitchClip;
use DiscordTwitchBot\Repository\GameRepository;
use DiscordTwitchBot\Repository\StreamerRepository;

class DiscordClipEmbedQuery
{
    public function __construct(
        private readonly Discord $discord,
        private readonly GameRepository $gameRepository,
        private readonly StreamerRepository $streamerRepository,
    ) {
    }

    public function generate(
        TwitchClip $clip,
    ): EmbeddedMessage {
        Benchmarker::getInstance()->start(__CLASS__);

        $gameName  = $this->gameRepository->get($clip->gameId);
        $gameField = $gameName ? ['name' => 'Game', 'value' => $gameName, 'inline' => true] : null;
        $streamer  = $this->streamerRepository->getStreamerById($clip->broadcasterId);

        $data = [
            'title'  => $clip->title . ' - Click to Watch on Twitch',
            'url'    => $clip->getUrl(),
            'image'  => ['url' => $clip->thumbnailUrl],
            'author' => [
                'name'     => $clip->broadcasterName,
            ],
            'color'  => hexdec('6a44bc'),
            'timestamp' => $clip->createdAt->format('c'),
            'fields' => array_filter([
                [
                    'name'  => 'Clipped By',
                    'value' => $clip->creatorName,
                    'inline' => true,
                ],
                $gameField,
            ]),
            'footer' => [
                'text' => 'Made by .justabignerd',
            ]
        ];

        if ($streamer !== null) {
            $data['author']['url'] = $streamer->getUrl();
            $data['author']['icon_url'] = $streamer->profileImageUrl . '?rand=' . time();
        }

        $embed = new Embed($this->discord, $data);

        Benchmarker::getInstance()->stop(__CLASS__);

        return new EmbeddedMessage("", $embed);
    }
}
