<?php

namespace DiscordTwitchBot;

use Discord\Discord;
use DiscordTwitchBot\Application\NotifyDiscordClips;
use DiscordTwitchBot\Application\NotifyDiscordLiveStreams;
use DiscordTwitchBot\Application\PushoverNotifier;
use DiscordTwitchBot\Application\Query\CheckStreamerExistsQuery;
use DiscordTwitchBot\Application\Query\DiscordClipEmbedQuery;
use DiscordTwitchBot\Application\Query\DiscordLiveEmbedQuery;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Repository\GameRepository;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use DiscordTwitchBot\Repository\NotifiedClipsRepository;
use DiscordTwitchBot\Repository\NotifiedStreamsRepository;
use DiscordTwitchBot\Repository\StreamerRepository;
use Psr\Log\LoggerInterface;

class NotifyBot
{
    private CheckStreamerExistsQuery $checkStreamerExistsQuery;

    public function __construct(
        private readonly Discord $discord,
        private readonly TwitchWrapper $twitchWrapper,
        private readonly LoggerInterface $logger,
        private readonly GuildSettingsRepository $guildSettingsRepository,
        private readonly NotifiedStreamsRepository $notifiedStreamsRepository,
        private readonly StreamerRepository $streamerRepository,
        private readonly GameRepository $gameRepository,
        private readonly NotifiedClipsRepository $notifiedClipsRepository,
    ) {
        $this->checkStreamerExistsQuery = new CheckStreamerExistsQuery(
            $this->logger,
            $this->streamerRepository,
            $this->guildSettingsRepository,
            $this->twitchWrapper,
            PushoverNotifier::factory(),
        );

    }

    public function run(): void
    {
        $this->discord->on('init', function () {
            $this->logger->info('Notify Cron is ready!');
        });

        $notify = new NotifyDiscordLiveStreams(
            $this->logger,
            $this->discord,
            $this->twitchWrapper,
            $this->guildSettingsRepository,
            $this->notifiedStreamsRepository,
            $this->streamerRepository,
            new DiscordLiveEmbedQuery($this->discord),
            $this->gameRepository,
            $this->checkStreamerExistsQuery,
        );

        $clipNotify = new NotifyDiscordClips(
            $this->logger,
            $this->discord,
            $this->twitchWrapper,
            $this->guildSettingsRepository,
            $this->streamerRepository,
            new DiscordClipEmbedQuery($this->discord, $this->gameRepository, $this->streamerRepository),
            $this->notifiedClipsRepository,
            $this->checkStreamerExistsQuery,
        );

        $this->discord->getLoop()->addPeriodicTimer(180, function () use ($notify) {
            $this->logger->info('Notifying Streams');
            try {
                $notify->execute();
            } catch (\Throwable $th) {
                $this->logger->error($th->getMessage());
                $this->logger->error($th->getTraceAsString());
            }
        });

        $this->discord->getLoop()->addPeriodicTimer(60 * 5, function () use ($clipNotify) {
            $this->logger->info('Notifying clips');
            try {
                $clipNotify->execute();
            } catch (\Throwable $th) {
                $this->logger->error($th->getMessage());
                $this->logger->error($th->getTraceAsString());
            }
        });

        $this->discord->getLoop()->addPeriodicTimer(15, function () {
            touch(__DIR__ . '/../notifier.lock');
        });

        $this->discord->getLoop()->futureTick(function () {
            $this->logger->info('Notify Bot is running');
        });

        $this->discord->run();
    }
}
