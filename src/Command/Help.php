<?php

namespace DiscordTwitchBot\Command;

use Discord\Discord;
use Discord\Parts\Channel\Message;

class Help extends Command
{
    public function __construct(
        private readonly Discord $discord,
    )
    {
    }

    public function getName(): string
    {
        return 'help';
    }

    public function execute(Message $message): void
    {
        $message->reply(
            "**Commands:**\n" .
            "- `add <username> [custom message]` - Add a streamer to watch. Optionally set a custom message ({name} and {game} are available parameters).\n" .
            "- `remove <username>` - Remove a streamer from the watch list.\n" .
            "- `channel <channel>` - Set the channel to post notifications in. Default is announcements.\n" .
            "- `prefix <prefix>` - Set the bot prefix. Default is `!sb`.\n" .
            "- `help` - Show this message.\n" .
            "- `list` - List all streamers being watched.\n" .
            "\n" .
            "**Example:**\n" .
            "- `!sb add j0hnn0o OI OI, come watch me! I'm playing {game}`\n" .
            "- `!sb channel announcements\n"
        );
    }
}
