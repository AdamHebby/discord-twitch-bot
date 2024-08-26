<?php

namespace DiscordTwitchBot\Test\Application;

use Discord\Discord;
use Discord\Parts\Embed\Embed;
use PHPUnit\Framework\TestCase;
use DiscordTwitchBot\Application\NotifyDiscordLiveStreams;
use DiscordTwitchBot\Application\Query\DiscordLiveEmbedQuery;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Model\GuildSettings;
use DiscordTwitchBot\Model\Stream;
use DiscordTwitchBot\Model\Streamer;
use DiscordTwitchBot\Model\TwitchClip;
use DiscordTwitchBot\Repository\GameRepository;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use DiscordTwitchBot\Repository\NotifiedStreamsRepository;
use DiscordTwitchBot\Repository\StreamerRepository;
use DiscordTwitchBot\Test\DiscordMocker;
use Psr\Log\LoggerInterface;

use function React\Promise\resolve;

class NotifyDiscordLiveStreamsTest extends TestCase
{
    use DiscordMocker;

    public function testExecuteHappyPath(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $discord = $this->createMock(Discord::class);
        $twitchWrapper = $this->createMock(TwitchWrapper::class);
        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);
        $streamerRepository = $this->createMock(StreamerRepository::class);
        $notifiedStreamsRepository = $this->createMock(NotifiedStreamsRepository::class);
        $gameRepository = $this->createMock(GameRepository::class);

        $gameRepository->expects($this->exactly(3))
            ->method('add');

        $discord->expects($this->once())
            ->method('__get')
            ->with('guilds')
            ->willReturn([
                $this->mockGuild([['id', '1'], ['name', '1']]),
                $this->mockGuild([['id', '2'], ['name', '2']]),
                $this->mockGuild([['id', '3'], ['name', '3']]),
            ]);

        $guildSettingsRepository->expects($this->any())
            ->method('load')
            ->willReturnMap([
                ['1', $this->getGuildSettings('1')],
                ['2', $this->getGuildSettings('2')],
                ['3', $this->getGuildSettings('3')],
            ]);

        $twitchWrapper->expects($this->exactly(3))
            ->method('getStream')
            ->willReturnOnConsecutiveCalls(
                $this->getStream("1"),
                $this->getStream("2"),
                $this->getStream("3"),
            );

        $streamerRepository->expects($this->exactly(3))
            ->method('getStreamerByLogin')
            ->willReturnOnConsecutiveCalls(
                $this->getStreamer('1'),
                $this->getStreamer('2'),
                $this->getStreamer('3'),
            );

        $notifiedStreamsRepository->expects($this->exactly(3))
            ->method('isNotified')
            ->willReturn(false);

        $notifiedStreamsRepository->expects($this->exactly(3))
            ->method('isNotifiedRestartedStream')
            ->willReturn(false);

        $channel1 = $this->mockChannel('channel-1');
        $channel2 = $this->mockChannel('channel-2');
        $channel3 = $this->mockChannel('channel-3');

        foreach ([$channel1, $channel2, $channel3] as $channel) {
            $channel->expects($this->once())
                ->method('sendMessage')
                ->with($this->anything(), false, $this->callback(function (Embed $embed) {
                    return true;
                }))
                ->willReturn(resolve(true));
        }

        $notifiedStreamsRepository->expects($this->exactly(3))
            ->method('add');

        $discord->expects($this->any())
            ->method('getChannel')
            ->willReturnMap([
                ['channel-1', $channel1],
                ['channel-2', $channel2],
                ['channel-3', $channel3],
            ]);

        $notifyDiscordClips = new NotifyDiscordLiveStreams(
            $logger,
            $discord,
            $twitchWrapper,
            $guildSettingsRepository,
            $notifiedStreamsRepository,
            $streamerRepository,
            $this->getEmbedQuery(),
            $gameRepository
        );

        $notifyDiscordClips->execute();
    }

    public function testExecuteStopsWhenStreamNotified(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $discord = $this->createMock(Discord::class);
        $twitchWrapper = $this->createMock(TwitchWrapper::class);
        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);
        $streamerRepository = $this->createMock(StreamerRepository::class);
        $notifiedStreamsRepository = $this->createMock(NotifiedStreamsRepository::class);
        $gameRepository = $this->createMock(GameRepository::class);

        $gameRepository->expects($this->exactly(3))
            ->method('add');

        $discord->expects($this->once())
            ->method('__get')
            ->with('guilds')
            ->willReturn([
                $this->mockGuild([['id', '1'], ['name', '1']]),
                $this->mockGuild([['id', '2'], ['name', '2']]),
                $this->mockGuild([['id', '3'], ['name', '3']]),
            ]);

        $guildSettingsRepository->expects($this->any())
            ->method('load')
            ->willReturnMap([
                ['1', $this->getGuildSettings('1')],
                ['2', $this->getGuildSettings('2')],
                ['3', $this->getGuildSettings('3')],
            ]);

        $twitchWrapper->expects($this->exactly(3))
            ->method('getStream')
            ->willReturnOnConsecutiveCalls(
                $this->getStream("1"),
                $this->getStream("2"),
                $this->getStream("3"),
            );

        $streamerRepository->expects($this->exactly(3))
            ->method('getStreamerByLogin')
            ->willReturnOnConsecutiveCalls(
                $this->getStreamer('1'),
                $this->getStreamer('2'),
                $this->getStreamer('3'),
            );

        $notifiedStreamsRepository->expects($this->exactly(3))
            ->method('isNotified')
            ->willReturn(true);

        $notifiedStreamsRepository->expects($this->never())
            ->method('add');

        $discord->expects($this->never())
            ->method('getChannel');

        $notifyDiscordClips = new NotifyDiscordLiveStreams(
            $logger,
            $discord,
            $twitchWrapper,
            $guildSettingsRepository,
            $notifiedStreamsRepository,
            $streamerRepository,
            $this->getEmbedQuery(),
            $gameRepository
        );

        $notifyDiscordClips->execute();
    }

    public function testExecuteStopsWhenStreamRestarted(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $discord = $this->createMock(Discord::class);
        $twitchWrapper = $this->createMock(TwitchWrapper::class);
        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);
        $streamerRepository = $this->createMock(StreamerRepository::class);
        $notifiedStreamsRepository = $this->createMock(NotifiedStreamsRepository::class);
        $gameRepository = $this->createMock(GameRepository::class);

        $gameRepository->expects($this->exactly(3))
            ->method('add');

        $discord->expects($this->once())
            ->method('__get')
            ->with('guilds')
            ->willReturn([
                $this->mockGuild([['id', '1'], ['name', '1']]),
                $this->mockGuild([['id', '2'], ['name', '2']]),
                $this->mockGuild([['id', '3'], ['name', '3']]),
            ]);

        $guildSettingsRepository->expects($this->any())
            ->method('load')
            ->willReturnMap([
                ['1', $this->getGuildSettings('1')],
                ['2', $this->getGuildSettings('2')],
                ['3', $this->getGuildSettings('3')],
            ]);

        $twitchWrapper->expects($this->exactly(3))
            ->method('getStream')
            ->willReturnOnConsecutiveCalls(
                $this->getStream("1"),
                $this->getStream("2"),
                $this->getStream("3"),
            );

        $streamerRepository->expects($this->exactly(3))
            ->method('getStreamerByLogin')
            ->willReturnOnConsecutiveCalls(
                $this->getStreamer('1'),
                $this->getStreamer('2'),
                $this->getStreamer('3'),
            );

        $notifiedStreamsRepository->expects($this->exactly(3))
            ->method('isNotified')
            ->willReturn(false);

        $notifiedStreamsRepository->expects($this->exactly(3))
            ->method('isNotifiedRestartedStream')
            ->willReturn(true);

        $notifiedStreamsRepository->expects($this->never())
            ->method('add');

        $discord->expects($this->never())
            ->method('getChannel');

        $notifyDiscordClips = new NotifyDiscordLiveStreams(
            $logger,
            $discord,
            $twitchWrapper,
            $guildSettingsRepository,
            $notifiedStreamsRepository,
            $streamerRepository,
            $this->getEmbedQuery(),
            $gameRepository
        );

        $notifyDiscordClips->execute();
    }

    public function testExecuteStopsWhenChannelNotFound(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $discord = $this->createMock(Discord::class);
        $twitchWrapper = $this->createMock(TwitchWrapper::class);
        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);
        $streamerRepository = $this->createMock(StreamerRepository::class);
        $notifiedStreamsRepository = $this->createMock(NotifiedStreamsRepository::class);
        $gameRepository = $this->createMock(GameRepository::class);

        $gameRepository->expects($this->exactly(3))
            ->method('add');

        $discord->expects($this->once())
            ->method('__get')
            ->with('guilds')
            ->willReturn([
                $this->mockGuild([['id', '1'], ['name', '1']]),
                $this->mockGuild([['id', '2'], ['name', '2']]),
                $this->mockGuild([['id', '3'], ['name', '3']]),
            ]);

        $guildSettingsRepository->expects($this->any())
            ->method('load')
            ->willReturnMap([
                ['1', $this->getGuildSettings('1')],
                ['2', $this->getGuildSettings('2')],
                ['3', $this->getGuildSettings('3')],
            ]);

        $twitchWrapper->expects($this->exactly(3))
            ->method('getStream')
            ->willReturnOnConsecutiveCalls(
                $this->getStream("1"),
                $this->getStream("2"),
                $this->getStream("3"),
            );

        $streamerRepository->expects($this->exactly(3))
            ->method('getStreamerByLogin')
            ->willReturnOnConsecutiveCalls(
                $this->getStreamer('1'),
                $this->getStreamer('2'),
                $this->getStreamer('3'),
            );

        $notifiedStreamsRepository->expects($this->exactly(3))
            ->method('isNotified')
            ->willReturn(false);

        $notifiedStreamsRepository->expects($this->exactly(3))
            ->method('isNotifiedRestartedStream')
            ->willReturn(false);

        $notifiedStreamsRepository->expects($this->never())
            ->method('add');

        $discord->expects($this->exactly(3))
            ->method('getChannel')
            ->willReturn(null);

        $notifyDiscordClips = new NotifyDiscordLiveStreams(
            $logger,
            $discord,
            $twitchWrapper,
            $guildSettingsRepository,
            $notifiedStreamsRepository,
            $streamerRepository,
            $this->getEmbedQuery(),
            $gameRepository
        );

        $notifyDiscordClips->execute();
    }

    public function testExecuteStopsWhenStreamNotFound(): void
    {

        $logger = $this->createMock(LoggerInterface::class);
        $discord = $this->createMock(Discord::class);
        $twitchWrapper = $this->createMock(TwitchWrapper::class);
        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);
        $streamerRepository = $this->createMock(StreamerRepository::class);
        $notifiedStreamsRepository = $this->createMock(NotifiedStreamsRepository::class);
        $gameRepository = $this->createMock(GameRepository::class);

        $gameRepository->expects($this->never())
            ->method('add');

        $discord->expects($this->once())
            ->method('__get')
            ->with('guilds')
            ->willReturn([
                $this->mockGuild([['id', '1'], ['name', '1']]),
                $this->mockGuild([['id', '2'], ['name', '2']]),
                $this->mockGuild([['id', '3'], ['name', '3']]),
            ]);

        $guildSettingsRepository->expects($this->any())
            ->method('load')
            ->willReturnMap([
                ['1', $this->getGuildSettings('1')],
                ['2', $this->getGuildSettings('2')],
                ['3', $this->getGuildSettings('3')],
            ]);

        $twitchWrapper->expects($this->exactly(3))
            ->method('getStream')
            ->willReturn(null);

        $streamerRepository->expects($this->never())
            ->method('getStreamerByLogin');

        $notifiedStreamsRepository->expects($this->never())
            ->method('isNotified')
            ->willReturn(false);

        $notifiedStreamsRepository->expects($this->never())
            ->method('isNotifiedRestartedStream')
            ->willReturn(false);

        $notifiedStreamsRepository->expects($this->never())
            ->method('add');

        $discord->expects($this->never())
            ->method('getChannel');

        $notifyDiscordClips = new NotifyDiscordLiveStreams(
            $logger,
            $discord,
            $twitchWrapper,
            $guildSettingsRepository,
            $notifiedStreamsRepository,
            $streamerRepository,
            $this->getEmbedQuery(),
            $gameRepository
        );

        $notifyDiscordClips->execute();
    }

    private function getGuildSettings(string $id): GuildSettings
    {
        $guildSettings = new GuildSettings($id);
        $guildSettings->addStreamer('streamer-' . $id, null, 'channel-' . $id);

        return $guildSettings;
    }

    private function getStreamer(string $id): Streamer
    {
        return Streamer::fromJsonArray([
            'id' => $id,
            'login' => 'streamer-' . $id,
            'display_name' => 'streamer-' . $id,
            'type' => 'unknown',
            'broadcaster_type' => 'affiliate',
            'description' => 'Test',
            'profile_image_url' => 'https://example.com/profile.jpg',
            'offline_image_url' => 'https://example.com/offline.jpg',
            'view_count' => 100,
            'created_at' => '2024-01-01T00:00:00+00:00',
        ]);
    }

    private function getStream(string $id): Stream
    {
        return Stream::fromJsonArray([
            "id" => $id,
            "user_id" => "456",
            "user_login" => "streamer-" . $id,
            "user_name" => "streamer-" . $id,
            "game_id" => "789",
            "game_name" => "game_name",
            "type" => "live",
            "title" => "title",
            "tags" => [],
            "viewer_count" => 100,
            "started_at" => "2024-01-01T00:00:00+00:00",
            "language" => "en",
            "thumbnail_url" => "https://example.com/{width}x{height}.jpg",
            "tag_ids" => [],
            "is_mature" => false,
        ]);
    }

    private function getEmbedQuery(): DiscordLiveEmbedQuery
    {
        $discord    = $this->createPartialMock(Discord::class, ['__construct', 'getFactory', 'getHttpClient']);
        $httpClient = $this->createMock(\Discord\Http\Http::class);
        $factory    = new \Discord\Factory\Factory($discord, $httpClient);

        $discord->method('getFactory')->willReturn($factory);
        $discord->method('getHttpClient')->willReturn($httpClient);

        $query = new DiscordLiveEmbedQuery($discord);

        return $query;
    }
}
