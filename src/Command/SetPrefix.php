<?php

namespace DiscordTwitchBot\Command;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Repository\GuildSettingsRepository;

class SetPrefix extends Command
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
        return 'prefix';
    }

    public function execute(Message $message): void
    {
        $args = self::getArgs($message);
        $guild = $message->channel->guild_id;

        if (count($args) !== 1) {
            $message->reply('Invalid number of arguments. Usage: !setPrefix <prefix>');
            return;
        }

        $prefix = "!" . ltrim(trim("$args[0]"), '!');
        $guildSettings = $this->guildSettingsRepository->load($guild);
        $guildSettings->setPrefix($prefix);
        $this->guildSettingsRepository->save($guildSettings);

        $message->reply("Prefix set to: `$prefix`");
    }
}
