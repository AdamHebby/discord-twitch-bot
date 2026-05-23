<?php

namespace DiscordTwitchBot\Application;

use Discord\Discord;
use Discord\Parts\Guild\Guild;
use DiscordTwitchBot\Application\Query\CheckStreamerExistsQuery;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use DiscordTwitchBot\Repository\NotifiedStreamsRepository;
use DiscordTwitchBot\Application\Query\DiscordLiveEmbedQuery;
use DiscordTwitchBot\Model\Stream;
use DiscordTwitchBot\Repository\GameRepository;
use DiscordTwitchBot\Repository\StreamerRepository;
use Psr\Log\LoggerInterface;

class NotifyDiscordLiveStreams
{
    /** @var array<string,Stream> $streamCache */
    private array $streamCache = [];

    /** @var array<string,bool> $offlineCache */
    private array $offlineCache = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Discord $discord,
        private readonly TwitchWrapper $twitchWrapper,
        private readonly GuildSettingsRepository $guildSettingsRepository,
        private readonly NotifiedStreamsRepository $notifiedStreamsRepository,
        private readonly StreamerRepository $streamerRepository,
        private readonly DiscordLiveEmbedQuery $discordLiveEmbedQuery,
        private readonly GameRepository $gameRepository,
        private readonly CheckStreamerExistsQuery $checkStreamerExistsQuery,
    ) {
    }

    public function execute(): void
    {
        Benchmarker::getInstance()->start(__CLASS__);
        $guilds = $this->discord->guilds;

        /** @var Guild $guild */
        foreach ($guilds as $guild) {
            $this->logger->debug("Notify: Checking guild {$guild->name}");
            $guildSettings = $this->guildSettingsRepository->load($guild->id);
            $streamers = $guildSettings->getStreamers();

            foreach ($streamers as $streamer) {
                $this->logger->debug("Notify: Checking streamer {$streamer}");

                if (!$this->checkStreamerExistsQuery->check($guildSettings, $streamer)) {
                    $this->logger->debug("Notify: Streamer {$streamer} does not exist. Skipping.");
                    continue;
                }

                $stream = $this->getStream($streamer);

                if ($stream === null) {
                    $this->logger->debug("Notify: Stream {$streamer} is offline. Skipping.");
                    continue;
                }

                if (!empty($stream->gameId) && !empty($stream->gameName)) {
                    $this->gameRepository->add($stream->gameId, $stream->gameName);
                }

                $this->notifyStream($guild, $stream);
            }
        }

        $this->streamCache = [];
        $this->offlineCache = [];

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

        $discordChannel->sendMessage($message->message, false, $message->embed)->then(
            function () use ($stream, $guild, $discordChannel) {
                $this->logger->info(sprintf(
                    "Notify: Stream %s notified in guild %s (%s) in channel %s (%s)",
                    $stream->id,
                    $guild->name,
                    $guild->id,
                    // @phpstan-ignore-next-line - name is is documented correctly, not sure why phpstan is complaining
                    $discordChannel->name,
                    $discordChannel->id
                ));

                $this->notifiedStreamsRepository->add($stream->userLogin, $stream->id, $guild->id);
            }
        );
    }

    private function getStream(string $streamer): ?Stream
    {
        if (isset($this->offlineCache[$streamer])) {
            return null;
        }

        if (!isset($this->streamCache[$streamer])) {
            $stream = $this->twitchWrapper->getStream($streamer);
            $this->streamCache[$streamer] = $stream;

            if ($stream === null) {
                $this->offlineCache[$streamer] = true;
            }
        }

        return $this->streamCache[$streamer];
    }
}
