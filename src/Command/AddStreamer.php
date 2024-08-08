<?php

namespace DiscordTwitchBot\Command;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Repository\GuildSettingsRepository;

class AddStreamer extends Command
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
        return 'add';
    }

    public function execute(Message $message): void
    {
        $args          = self::getArgs($message);
        $streamer      = $args[0];
        $guildSettings = $this->guildSettingsRepository->load($message->channel->guild_id);
        array_shift($args);
        $customMessage = implode(' ', $args);

        if ($guildSettings->hasStreamer($streamer)) {
            $message->reply("{$streamer} is already being watched!");
            return;
        }

        if (!$this->twitchApi->checkStreamerExists($streamer)) {
            $message->reply("{$streamer} is not a valid Twitch streamer!");
            return;
        }

        $guildSettings->addStreamer($streamer, $customMessage);
        $this->guildSettingsRepository->save($guildSettings);

        $message->reply("{$streamer} is now being watched!");
    }
}
