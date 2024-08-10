<?php

namespace DiscordTwitchBot\Command;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Psr\Log\LoggerInterface;

class Restart extends Command
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Discord $discord,
    ) {
    }

    public function getName(): string
    {
        return 'restart';
    }

    public function execute(Message $message): void
    {
        $this->logger->warning(sprintf(
            'User %s (%s) attempted to restart the bot',
            $message->author->username,
            $message->author->id
        ));

        if ($message->member->user->id !== '399256730749370369') {
            return;
        }

        $this->logger->warning('Restarting bot...');

        $message->react('✅')->done(function (Message $message) {
            $this->discord->close();
            unlink(__DIR__ . '/../../lock');
            exit;
        });
    }
}
