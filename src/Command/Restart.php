<?php

namespace DiscordTwitchBot\Command;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Monolog\Logger;

class Restart extends Command
{
    public function __construct(
        private readonly Logger $logger,
        private readonly Discord $discord,
    )
    {
    }

    public function getName(): string
    {
        return 'restart';
    }

    public function execute(Message $message): void
    {
        if ($message->member->user->id !== '399256730749370369') {
            return;
        }

        echo 'Admin requested a restart!' . PHP_EOL;

        $message->react('✅')->done(function (Message $message) {
            $this->discord->close();
            unlink(__DIR__ . '/../../lock');
            exit;
        });
    }
}
