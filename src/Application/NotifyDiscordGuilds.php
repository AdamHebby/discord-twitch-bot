<?php

namespace DiscordTwitchBot\Application;

use Discord\Builders\MessageBuilder;
use Discord\Discord;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use DiscordTwitchBot\Repository\NotifiedStreamsRepository;
use DiscordTwitchBot\Application\Query\DiscordLiveEmbedQuery;

class NotifyDiscordGuilds
{
    private readonly NotifiedStreamsRepository $notifiedStreamsRepository;

    public function __construct(
        private readonly Discord $discord,
        private readonly TwitchWrapper $twitchWrapper,
        private readonly GuildSettingsRepository $guildSettingsRepository,
    )
    {
        $this->notifiedStreamsRepository = new NotifiedStreamsRepository(__DIR__ . '/../data/notified_streams.db');
    }

    public function execute(): void
    {
        $guilds = $this->discord->guilds;
        $streamerCache = [];

        /** @var Guild $guild */
        foreach ($guilds as $guild) {
            $guildSettings = $this->guildSettingsRepository->load($guild->id);
            $streamers = $guildSettings->getStreamers();

            foreach ($streamers as $streamer) {
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
                    continue;
                }

                $customStreamerMessage = $guildSettings->getStreamerMessage($streamer);

                if ($customStreamerMessage === null) {
                    $customStreamerMessage = "Hey @everyone! {name} is now live on Twitch! " .
                        "They're playing {game}. Go check them out!";
                }

                $customStreamerMessage = str_replace('{name}', $stream->getEscapedUsername(), $customStreamerMessage);
                $customStreamerMessage = str_replace('{game}', $stream->getGameName(), $customStreamerMessage);

                $message = MessageBuilder::new()
                    ->setContent($customStreamerMessage)
                    ->addEmbed(DiscordLiveEmbedQuery::generate($this->discord, $stream));

                $channel = $guildSettings->getChannel();

                $guild->channels->get('name', $channel)->sendMessage($message);

                $this->notifiedStreamsRepository->add($stream->getId(), $guild->id);
            }
        }
    }
}