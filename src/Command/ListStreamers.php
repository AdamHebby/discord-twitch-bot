<?php

namespace DiscordTwitchBot\Command;

use Discord\Parts\Interactions\Interaction;
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

    public function getDescription(): string
    {
        return 'List all streamers being watched.';
    }

    public function getOptions(): array
    {
        return [];
    }

    public function execute(Interaction $interaction): void
    {
        Benchmarker::getInstance()->start(__CLASS__);

        $streamers = $this->guildSettingsRepository->load($interaction->channel->guild_id)->getStreamers();

        if (empty($streamers)) {
            $this->interactionReply($interaction, 'No streamers are being watched!');
            return;
        }

        $this->interactionReply($interaction, 'Streamers being watched: ' . implode(', ', $streamers));

        $this->logger->info(sprintf(
            'Streamers List shown to user %s (%s) in guild "%s" (%s)',
            $interaction->member->username,
            $interaction->member->id,
            $interaction->channel->guild->name,
            $interaction->channel->guild->id
        ));

        Benchmarker::getInstance()->stop(__CLASS__);
    }
}
