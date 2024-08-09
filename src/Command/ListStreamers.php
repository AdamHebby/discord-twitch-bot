<?php

namespace DiscordTwitchBot\Command;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use Monolog\Logger;

class ListStreamers extends Command
{
    public function __construct(
        private readonly Logger $logger,
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
        Benchmarker::getInstance()->start(__CLASS__);

        $streamers = $this->guildSettingsRepository->load($message->channel->guild_id)->getStreamers();

        if (empty($streamers)) {
            $message->reply('No streamers are being watched!');
            return;
        }

        $message->reply('Streamers being watched: ' . implode(', ', $streamers));

        Benchmarker::getInstance()->stop(__CLASS__);
    }
}
