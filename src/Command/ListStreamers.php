<?php

namespace DiscordTwitchBot\Command;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use DiscordTwitchBot\Repository\GuildSettingsRepository;

class ListStreamers extends Command
{
    public function __construct(
        private readonly Discord $discord,
        private readonly GuildSettingsRepository $guildSettingsRepository,
    )
    {
    }

    public function getName(): string
    {
        return 'list';
    }

    public function execute(Message $message): void
    {
        $streamers = $this->guildSettingsRepository->load($message->channel->guild_id)->getStreamers();

        if (empty($streamers)) {
            $message->reply('No streamers are being watched!');
            return;
        }

        $message->reply('Streamers being watched: ' . implode(', ', $streamers));
    }
}
