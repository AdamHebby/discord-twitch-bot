<?php

namespace DiscordTwitchBot\Application;

use Discord\Discord;
use Discord\Parts\Guild\Guild;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use DiscordTwitchBot\Repository\NotifiedStreamsRepository;
use DiscordTwitchBot\Application\Query\DiscordLiveEmbedQuery;
use Monolog\Logger;

class NotifyDiscordGuilds
{
    private readonly NotifiedStreamsRepository $notifiedStreamsRepository;

    public function __construct(
        private readonly Logger $logger,
        private readonly Discord $discord,
        private readonly TwitchWrapper $twitchWrapper,
        private readonly GuildSettingsRepository $guildSettingsRepository,
    ) {
        $this->notifiedStreamsRepository = new NotifiedStreamsRepository(__DIR__ . '/../data/notified_streams.db');
    }

    public function execute(): void
    {
        Benchmarker::getInstance()->start(__CLASS__);
        $guilds = $this->discord->guilds;
        $streamerCache = [];

        /** @var Guild $guild */
        foreach ($guilds as $guild) {
            $this->logger->debug("Notify: Checking guild {$guild->name}");
            $guildSettings = $this->guildSettingsRepository->load($guild->id);
            $streamers = $guildSettings->getStreamers();

            foreach ($streamers as $streamer) {
                $this->logger->debug("Notify: Checking streamer {$streamer}");
                if (isset($streamerCache[$streamer])) {
                    $stream = $streamerCache[$streamer];
                } else {
                    $stream = $this->twitchWrapper->getStream($streamer);
                    $streamerCache[$streamer] = $stream;
                }

                if ($stream === null) {
                    continue;
                }

                if ($this->notifiedStreamsRepository->isNotified($stream->getId(), $guild->id)) {
                    $this->logger->debug("Notify: Stream {$stream->getId()} already notified in guild {$guild->name}");
                    continue;
                }

                $message = DiscordLiveEmbedQuery::generate($guildSettings, $this->discord, $stream);
                $channel = $guildSettings->getChannel();

                $discordChannel = $guild->channels->get('id', $channel);
                if ($discordChannel === null) {
                    $discordChannel = $guild->channels->get('id', $channel);
                } else {
                    $guildSettings->setChannel($discordChannel->id);
                    $this->guildSettingsRepository->save($guildSettings);
                }

                if ($discordChannel === null) {
                    $this->logger->error("Notify: Channel {$channel} not found in guild {$guild->name}");
                    continue;
                }

                $discordChannel->sendMessage($message)->done(function () use ($stream, $guild) {
                    $this->logger->info("Notify: Stream {$stream->getId()} notified in guild {$guild->name}");
                    $this->notifiedStreamsRepository->add($stream->getId(), $guild->id);
                });
            }
        }

        Benchmarker::getInstance()->stop(__CLASS__);
    }
}
