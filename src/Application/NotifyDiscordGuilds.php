<?php

namespace DiscordTwitchBot\Application;

use Discord\Discord;
use Discord\Parts\Guild\Guild;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use DiscordTwitchBot\Repository\NotifiedStreamsRepository;
use DiscordTwitchBot\Application\Query\DiscordLiveEmbedQuery;
use DiscordTwitchBot\Model\Stream;
use Psr\Log\LoggerInterface;

class NotifyDiscordGuilds
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Discord $discord,
        private readonly TwitchWrapper $twitchWrapper,
        private readonly GuildSettingsRepository $guildSettingsRepository,
        private readonly NotifiedStreamsRepository $notifiedStreamsRepository,
    ) {
    }

    public function execute(): void
    {
        Benchmarker::getInstance()->start(__CLASS__);
        $guilds = $this->discord->guilds;

        /** @var array<string, Stream> */
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
                    $this->logger->debug(
                        "Notify: Stream {$stream->getId()} already notified in guild {$guild->name}. Skipping."
                    );
                    continue;
                }

                if ($this->notifiedStreamsRepository->isNotifiedRestartedStream($stream->getUserLogin(), $guild->id)) {
                    $this->logger->debug(
                        "Notify: Stream {$stream->getId()} restarted in guild {$guild->name}. Skipping."
                    );
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

                $discordChannel->sendMessage($message->message, false, $message->embed)->done(
                    function () use ($stream, $guild) {
                        $this->logger->info("Notify: Stream {$stream->getId()} notified in guild {$guild->name}");
                        $this->notifiedStreamsRepository->add($stream->getUserLogin(), $stream->getId(), $guild->id);
                    }
                );
            }
        }

        Benchmarker::getInstance()->stop(__CLASS__);
    }
}
