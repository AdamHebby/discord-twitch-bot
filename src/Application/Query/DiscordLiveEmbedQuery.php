<?php

namespace DiscordTwitchBot\Application\Query;

use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Embed\Embed;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Model\GuildSettings;
use DiscordTwitchBot\Model\Stream;

class DiscordLiveEmbedQuery
{
    public static function generate(
        GuildSettings $guildSettings,
        Discord $discord,
        Stream $stream
    ): MessageBuilder {
        Benchmarker::getInstance()->start(__CLASS__);

        $streamUrl   = "https://www.twitch.tv/{$stream->getUserLogin()}?referrer=discord";
        $previewLink = $stream->getThumbnailUrl();
        $previewLink = str_replace('{width}', '1920', $previewLink);
        $previewLink = str_replace('{height}', '1080', $previewLink);

        $embed = new Embed($discord, [
            'title'  => $stream->getTitle(),
            'url'    => $streamUrl,
            'image'  => ['url' => $previewLink],
            'author' => [
                'name'     => $stream->getUserName() . " is now live on Twitch!",
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

        $customStreamerMessage = $guildSettings->getStreamerMessage($stream->getUserLogin());

        if ($customStreamerMessage === null) {
            $customStreamerMessage = "Hey @everyone! {name} is now live on Twitch! " .
                "They're playing {game}. Go check them out!";
        }

        $customStreamerMessage = str_replace('{name}', $stream->getEscapedUsername(), $customStreamerMessage);
        $customStreamerMessage = str_replace('{game}', $stream->getGameName(), $customStreamerMessage);

        $builder = new MessageBuilder();
        $builder->setContent($customStreamerMessage);
        $builder->addEmbed($embed);

        Benchmarker::getInstance()->stop(__CLASS__);

        return $builder;
    }
}
