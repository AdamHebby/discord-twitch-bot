<?php

namespace DiscordTwitchBot\Application\Query;

use Discord\Discord;
use Discord\Parts\Embed\Embed;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Application\EmbeddedMessage;
use DiscordTwitchBot\Model\GuildSettings;
use DiscordTwitchBot\Model\Stream;
use DiscordTwitchBot\Model\Streamer;

class DiscordLiveEmbedQuery
{
    public function __construct(
        private readonly Discord $discord,
    ) {
    }

    public const DEFAULT_MSG = "Hey @everyone! {name} is now live on Twitch! "
        . "They're playing {game}. Go check them out!";

    public function generate(
        GuildSettings $guildSettings,
        Streamer $streamer,
        Stream $stream
    ): EmbeddedMessage {
        Benchmarker::getInstance()->start(__CLASS__);

        $streamUrl = "https://www.twitch.tv/{$stream->userLogin}?referrer=discord";

        $embed = new Embed($this->discord, [
            'title'  => $stream->title,
            'url'    => $streamUrl,
            'image'  => ['url' => $stream->getThumbnailUrl()],
            'author' => [
                'name'     => $stream->userName . " is now live on Twitch!",
                'url'      => $streamUrl,
                'icon_url' => $streamer->profileImageUrl,
            ],
            'color'  => hexdec('6a44bc'),
            'timestamp' => $stream->startedAt->format('c'),
            'fields' => [
                [
                    'name'  => 'Game',
                    'value' => $stream->gameName,
                ]
            ],
            'footer' => [
                'text' => 'Made by .justabignerd',
            ]
        ]);

        $customStreamerMessage = $guildSettings->getStreamerMessage($stream->userLogin);

        if ($customStreamerMessage === null) {
            $customStreamerMessage = self::DEFAULT_MSG;
        }

        $customStreamerMessage = str_replace('{name}', $stream->getEscapedUsername(), $customStreamerMessage);
        $customStreamerMessage = str_replace('{game}', $stream->gameName, $customStreamerMessage);

        Benchmarker::getInstance()->stop(__CLASS__);

        return new EmbeddedMessage($customStreamerMessage, $embed);
    }
}
