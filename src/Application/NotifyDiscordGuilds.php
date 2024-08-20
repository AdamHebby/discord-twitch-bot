<?php

namespace DiscordTwitchBot\Application;

use Discord\Discord;
use Discord\Parts\Guild\Guild;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use DiscordTwitchBot\Repository\NotifiedStreamsRepository;
use DiscordTwitchBot\Application\Query\DiscordLiveEmbedQuery;
use DiscordTwitchBot\Model\Stream;
use DiscordTwitchBot\Repository\StreamerRepository;
use Psr\Log\LoggerInterface;

class NotifyDiscordGuilds
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Discord $discord,
        private readonly TwitchWrapper $twitchWrapper,
        private readonly GuildSettingsRepository $guildSettingsRepository,
        private readonly NotifiedStreamsRepository $notifiedStreamsRepository,
        private readonly StreamerRepository $streamerRepository,
        private readonly DiscordLiveEmbedQuery $discordLiveEmbedQuery,
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

                $stream = $streamerCache[$streamer] ?? null;
                if ($stream === null) {
                    $stream = $this->twitchWrapper->getStream($streamer);
                    $streamerCache[$streamer] = $stream;
                }

                if ($stream === null) {
                    $this->logger->debug("Notify: Stream {$streamer} is offline. Skipping.");
                    continue;
                }

                $this->notifyStream($guild, $stream);
            }
        }

        Benchmarker::getInstance()->stop(__CLASS__);
    }

    private function notifyStream(Guild $guild, Stream $stream): void
    {
        $streamer = $this->streamerRepository->getStreamerByLogin($stream->userLogin);
        $guildSettings = $this->guildSettingsRepository->load($guild->id);

        if ($this->notifiedStreamsRepository->isNotified($stream->id, $guild->id)) {
            $this->logger->debug(
                "Notify: Stream {$stream->id} already notified in guild {$guild->name}. Skipping."
            );
            return;
        }

        if ($this->notifiedStreamsRepository->isNotifiedRestartedStream($stream->userLogin, $guild->id)) {
            $this->logger->debug(
                "Notify: Stream {$stream->id} restarted in guild {$guild->name}. Skipping."
            );
            return;
        }

        $message = $this->discordLiveEmbedQuery->generate($guildSettings, $streamer, $stream);
        $channel = $guildSettings->getStreamerChannel($stream->userLogin);

        $discordChannel = $this->discord->getChannel($channel);

        if ($discordChannel === null) {
            $this->logger->error("Notify: Channel {$channel} not found in guild {$guild->name}");
            return;
        }

        $discordChannel->sendMessage($message->message, false, $message->embed)->done(
            function () use ($stream, $guild) {
                $this->logger->info("Notify: Stream {$stream->id} notified in guild {$guild->name}");
                $this->notifiedStreamsRepository->add($stream->userLogin, $stream->id, $guild->id);
            }
        );
    }
}
