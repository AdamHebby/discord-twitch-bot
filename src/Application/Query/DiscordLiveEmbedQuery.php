<?php

namespace DiscordTwitchBot\Application\Query;

use Discord\Discord;
use Discord\Parts\Embed\Embed;
use DiscordTwitchBot\Model\Stream;

class DiscordLiveEmbedQuery
{
    public static function generate(Discord $discord, Stream $stream): Embed
    {
        $streamUrl   = "https://www.twitch.tv/{$stream->getUserLogin()}?referrer=discord";
        $previewLink = $stream->getThumbnailUrl();
        $previewLink = str_replace('{width}', '1920', $previewLink);
        $previewLink = str_replace('{height}', '1080', $previewLink);

        $embed = new Embed($discord, [
            'title'  => $stream->getTitle(),
            'url'    => $streamUrl,
            'image'  => ['url' => $previewLink],
            'author' => [
                'name'     => $stream->getEscapedUsername() . " is now live on Twitch!",
                'url'      => $streamUrl,
                'icon_url' => 'https://icons.iconarchive.com/icons/papirus-team/papirus-apps/512/gnome-twitch-icon.png',
            ],
            'color'  => hexdec('6a44bc'),
            'timestamp' => $stream->getStartedAt()->format('c'),
        ]);

        $embed->addField([
            'name' => 'Game',
            'value' => $stream->getGameName(),
        ]);

        // add Footer
        $embed->setFooter(
            'Made by .justabignerd',
        );

        return $embed;
    }
}