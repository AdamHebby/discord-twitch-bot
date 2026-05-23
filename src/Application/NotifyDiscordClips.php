<?php

namespace DiscordTwitchBot\Application;

use DateTimeImmutable;
use DateTimeZone;
use Discord\Discord;
use Discord\Parts\Guild\Guild;
use DiscordTwitchBot\Application\Query\CheckStreamerExistsQuery;
use DiscordTwitchBot\Application\Query\DiscordClipEmbedQuery;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use DiscordTwitchBot\Repository\NotifiedClipsRepository;
use DiscordTwitchBot\Repository\StreamerRepository;
use Psr\Log\LoggerInterface;

class NotifyDiscordClips
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Discord $discord,
        private readonly TwitchWrapper $twitchWrapper,
        private readonly GuildSettingsRepository $guildSettingsRepository,
        private readonly StreamerRepository $streamerRepository,
        private readonly DiscordClipEmbedQuery $discordClipEmbedQuery,
        private readonly NotifiedClipsRepository $notifiedClipsRepository,
        private readonly CheckStreamerExistsQuery $checkStreamerExistsQuery,
    ) {
    }

    public function execute(): void
    {
        Benchmarker::getInstance()->start(__CLASS__);
        $guilds = $this->discord->guilds;
        $utc = new DateTimeZone('UTC');

        /** @var Guild $guild */
        foreach ($guilds as $guild) {
            $this->logger->debug("Notify: Checking guild {$guild->name}");
            $guildSettings = $this->guildSettingsRepository->load($guild->id);
            $streamers = $guildSettings->getClipChannels();

            foreach ($streamers as $streamer => $clipChannel) {
                $this->logger->debug("Notify: Checking streamer {$streamer}");

                if (!$this->checkStreamerExistsQuery->check($guildSettings, $streamer)) {
                    $this->logger->warning("Notify: Streamer {$streamer} does not exist. Skipping.");
                    continue;
                }

                $streamerObj = $this->streamerRepository->getStreamerByLogin($streamer);

                if ($streamerObj === null) {
                    $streamerObj = $this->twitchWrapper->getStreamer($streamer);

                    if ($streamerObj === null) {

                        continue;
                    }

                    $this->streamerRepository->add($streamerObj);
                }

                $now = new DateTimeImmutable('now', $utc);

                $recentClips = $this->twitchWrapper->getClips(
                    $streamerObj->id,
                    $now->modify('-24 hours'),
                    $now->modify('+1 second')
                );

                foreach ($recentClips as $clip) {
                    if ($this->notifiedClipsRepository->isNotified($clip->id, $guild->id)) {
                        $this->logger->debug(
                            "Notify: Clip {$clip->id} already notified in guild {$guild->name}. Skipping."
                        );
                        continue;
                    }

                    $message = $this->discordClipEmbedQuery->generate($clip);
                    $discordChannel = $this->discord->getChannel($clipChannel);

                    if ($discordChannel === null) {
                        $this->logger->error("Notify: Channel {$clipChannel} not found in guild {$guild->name}");
                        continue;
                    }

                    $discordChannel->sendMessage($message->message, false, $message->embed)->then(
                        function () use ($clip, $guild) {
                            $this->logger->info("Notify: Clip {$clip->id} notified in guild {$guild->name}");
                            $this->notifiedClipsRepository->add($clip->id, $guild->id);
                        }
                    );
                }
            }
        }

        Benchmarker::getInstance()->stop(__CLASS__);
    }
}
