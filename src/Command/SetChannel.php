<?php

namespace DiscordTwitchBot\Command;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Repository\GuildSettingsRepository;

class SetChannel extends Command
{
    public function __construct(
        private readonly Discord $discord,
        private readonly TwitchWrapper $twitchApi,
        private readonly GuildSettingsRepository $guildSettingsRepository,
    )
    {
    }

    public function getName(): string
    {
        return 'channel';
    }

    public function execute(Message $message): void
    {
        $args          = self::getArgs($message);
        $channel       = $args[0];
        $guildSettings = $this->guildSettingsRepository->load($message->channel->guild_id);

        $canSeeChannel = $message->guild->channels->get('name', $channel) !== null;

        if (!$canSeeChannel) {
            $message->reply("I can't see the channel {$channel}.");
            return;
        }

        $guildSettings->setChannel($channel);
        $this->guildSettingsRepository->save($guildSettings);
    }
}
