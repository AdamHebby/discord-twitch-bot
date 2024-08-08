<?php

namespace DiscordTwitchBot\Command;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Repository\GuildSettingsRepository;

class RemoveStreamer extends Command
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
        return 'remove';
    }

    public function execute(Message $message): void
    {
        $args          = self::getArgs($message);
        $streamer      = $args[0];
        $guildSettings = $this->guildSettingsRepository->load($message->channel->guild_id);

        if ($guildSettings->hasStreamer($streamer)) {
            $message->reply("{$streamer} is not being watched!");
            return;
        }

        $guildSettings->removeStreamer($streamer);
        $this->guildSettingsRepository->save($guildSettings);

        $message->reply("{$streamer} is no longer being watched!");
    }
}
