<?php

namespace DiscordTwitchBot\Command;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use Psr\Log\LoggerInterface;

class ListStreamers extends Command
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly GuildSettingsRepository $guildSettingsRepository,
    ) {
    }

    public function getName(): string
    {
        return 'list';
    }

    public function execute(Message $message): void
    {
        Benchmarker::getInstance()->start(__CLASS__);

        $streamers = $this->guildSettingsRepository->load($message->channel->guild_id)->getStreamers();

        if (empty($streamers)) {
            $message->reply('No streamers are being watched!');
            return;
        }

        $message->reply('Streamers being watched: ' . implode(', ', $streamers));

        $this->logger->info(sprintf(
            'Streamers List shown to user %s (%s) in guild "%s" (%s)',
            $message->author->username,
            $message->author->id,
            $message->channel->guild->name,
            $message->channel->guild->id
        ));

        Benchmarker::getInstance()->stop(__CLASS__);
    }
}
