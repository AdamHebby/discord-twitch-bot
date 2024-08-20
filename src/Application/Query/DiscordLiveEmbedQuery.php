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
    public const DEFAULT_MSG = "Hey @everyone! {name} is now live on Twitch! They're playing {game}. Go check them out!";

    public static function generate(
        GuildSettings $guildSettings,
        Discord $discord,
        Streamer $streamer,
        Stream $stream
    ): EmbeddedMessage {
        Benchmarker::getInstance()->start(__CLASS__);

        $streamUrl   = "https://www.twitch.tv/{$stream->userLogin}?referrer=discord";
        $previewLink = $stream->getThumbnailUrl();
        $previewLink = str_replace('{width}', '1920', $previewLink);
        $previewLink = str_replace('{height}', '1080', $previewLink);

        $embed = new Embed($discord, [
            'title'  => $stream->title,
            'url'    => $streamUrl,
            'image'  => ['url' => $previewLink],
            'author' => [
                'name'     => $stream->userName . " is now live on Twitch!",
                'url'      => $streamUrl,
                'icon_url' => $streamer->profileImageUrl,
            ],
            'color'  => hexdec('6a44bc'),
            'timestamp' => $stream->startedAt->format('c'),
        ]);

        $embed->addField([
            'name' => 'Game',
            'value' => $stream->gameName,
        ]);

        // add Footer
        $embed->setFooter('Made by .justabignerd');

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
