<?php

namespace DiscordTwitchBot\Command;

use Discord\Parts\Interactions\Interaction;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Application\Query\DiscordLiveEmbedQuery;
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

        $settings = $this->guildSettingsRepository->load($interaction->guild_id);
        $streamers = $settings->getStreamers();

        if (empty($streamers)) {
            $this->interactionReply($interaction, 'No streamers are being watched!');
            return;
        }

        $message = '**Streamers being watched:**' . PHP_EOL;
        foreach ($streamers as $streamer) {
            $streamerChannel = $settings->getStreamerChannel($streamer);
            $streamerMessage = $settings->getStreamerMessage($streamer);

            $message .= sprintf(
                '- `%s` in <#%s> with message: `%s`' . PHP_EOL,
                $streamer,
                $streamerChannel,
                $streamerMessage ?? DiscordLiveEmbedQuery::DEFAULT_MSG
            );
        }

        $this->interactionReply($interaction, $message);

        $this->logger->info(sprintf(
            'Streamers List shown to user %s (%s) in guild "%s" (%s)',
            $interaction->member->username,
            $interaction->member->id,
            $interaction->guild->name,
            $interaction->guild_id
        ));

        Benchmarker::getInstance()->stop(__CLASS__);
    }
}
