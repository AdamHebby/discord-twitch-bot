<?php

namespace DiscordTwitchBot\Test\Application;

use Discord\Discord;
use Discord\Parts\Embed\Embed;
use PHPUnit\Framework\TestCase;
use DiscordTwitchBot\Application\NotifyDiscordClips;
use DiscordTwitchBot\Application\Query\DiscordClipEmbedQuery;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Model\GuildSettings;
use DiscordTwitchBot\Model\Streamer;
use DiscordTwitchBot\Model\TwitchClip;
use DiscordTwitchBot\Repository\GameRepository;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use DiscordTwitchBot\Repository\NotifiedClipsRepository;
use DiscordTwitchBot\Repository\StreamerRepository;
use DiscordTwitchBot\Test\DiscordMocker;
use Psr\Log\LoggerInterface;

use function React\Promise\resolve;

class NotifyDiscordClipsTest extends TestCase
{
    use DiscordMocker;

    public function testExecuteHappyPath(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $discord = $this->createMock(Discord::class);
        $twitchWrapper = $this->createMock(TwitchWrapper::class);
        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);
        $streamerRepository = $this->createMock(StreamerRepository::class);
        $notifiedClipsRepository = $this->createMock(NotifiedClipsRepository::class);

        $discord->expects($this->once())
            ->method('__get')
            ->with('guilds')
            ->willReturn([
                $this->mockGuild([['id', '1'], ['name', '1']]),
                $this->mockGuild([['id', '2'], ['name', '2']]),
                $this->mockGuild([['id', '3'], ['name', '3']]),
            ]);

        $guildSettingsRepository->expects($this->exactly(3))
            ->method('load')
            ->willReturnMap([
                ['1', $this->getGuildSettings('1')],
                ['2', $this->getGuildSettings('2')],
                ['3', $this->getGuildSettings('3')],
            ]);

        $streamerRepository->expects($this->exactly(3))
            ->method('getStreamerByLogin')
            ->willReturnOnConsecutiveCalls(
                $this->getStreamer('1'),
                $this->getStreamer('2'),
                $this->getStreamer('3'),
            );

        $twitchWrapper->expects($this->exactly(3))
            ->method('getClips')
            ->willReturnOnConsecutiveCalls(
                [$this->getClip("1")],
                [$this->getClip("2")],
                [$this->getClip("3")],
            );

        $notifiedClipsRepository->expects($this->exactly(3))
            ->method('isNotified')
            ->willReturn(false);

        $channel1 = $this->mockChannel('channel-1');
        $channel2 = $this->mockChannel('channel-2');
        $channel3 = $this->mockChannel('channel-3');

        foreach ([$channel1, $channel2, $channel3] as $channel) {
            $channel->expects($this->once())
                ->method('sendMessage')
                ->with($this->anything(), false, $this->callback(function (Embed $embed) {
                    return $embed->title === 'Clip Title';
                }))
                ->willReturn(resolve());
        }

        $notifiedClipsRepository->expects($this->exactly(3))
            ->method('add');

        $discord->expects($this->exactly(3))
            ->method('getChannel')
            ->willReturnMap([
                ['channel-1', $channel1],
                ['channel-2', $channel2],
                ['channel-3', $channel3],
            ]);

        $notifyDiscordClips = new NotifyDiscordClips(
            $logger,
            $discord,
            $twitchWrapper,
            $guildSettingsRepository,
            $streamerRepository,
            $this->getEmbedQuery(),
            $notifiedClipsRepository
        );

        $notifyDiscordClips->execute();
    }

    public function testExecuteChannelNotFound(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $discord = $this->createMock(Discord::class);
        $twitchWrapper = $this->createMock(TwitchWrapper::class);
        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);
        $streamerRepository = $this->createMock(StreamerRepository::class);
        $notifiedClipsRepository = $this->createMock(NotifiedClipsRepository::class);

        $discord->expects($this->once())
            ->method('__get')
            ->with('guilds')
            ->willReturn([
                $this->mockGuild([['id', '1'], ['name', '1']]),
            ]);

        $guildSettingsRepository->expects($this->exactly(1))
            ->method('load')
            ->willReturnMap([
                ['1', $this->getGuildSettings('1')],
            ]);

        $streamerRepository->expects($this->exactly(1))
            ->method('getStreamerByLogin')
            ->willReturnOnConsecutiveCalls(
                $this->getStreamer('1'),
            );

        $twitchWrapper->expects($this->exactly(1))
            ->method('getClips')
            ->willReturnOnConsecutiveCalls(
                [$this->getClip("1")],
            );

        $notifiedClipsRepository->expects($this->exactly(1))
            ->method('isNotified')
            ->willReturn(false);

        $notifiedClipsRepository->expects($this->never())
            ->method('add');

        $discord->expects($this->exactly(1))
            ->method('getChannel')
            ->willReturnMap([
                ['channel-1', null],
            ]);

        $notifyDiscordClips = new NotifyDiscordClips(
            $logger,
            $discord,
            $twitchWrapper,
            $guildSettingsRepository,
            $streamerRepository,
            $this->getEmbedQuery(),
            $notifiedClipsRepository
        );

        $notifyDiscordClips->execute();
    }

    public function testExecuteClipAlreadyNotified(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $discord = $this->createMock(Discord::class);
        $twitchWrapper = $this->createMock(TwitchWrapper::class);
        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);
        $streamerRepository = $this->createMock(StreamerRepository::class);
        $notifiedClipsRepository = $this->createMock(NotifiedClipsRepository::class);

        $discord->expects($this->once())
            ->method('__get')
            ->with('guilds')
            ->willReturn([
                $this->mockGuild([['id', '1'], ['name', '1']]),
            ]);

        $guildSettingsRepository->expects($this->exactly(1))
            ->method('load')
            ->willReturnMap([
                ['1', $this->getGuildSettings('1')],
            ]);

        $streamerRepository->expects($this->exactly(1))
            ->method('getStreamerByLogin')
            ->willReturnOnConsecutiveCalls(
                $this->getStreamer('1'),
            );

        $twitchWrapper->expects($this->exactly(1))
            ->method('getClips')
            ->willReturnOnConsecutiveCalls(
                [$this->getClip("1")],
            );

        $notifiedClipsRepository->expects($this->exactly(1))
            ->method('isNotified')
            ->willReturn(true);

        $notifiedClipsRepository->expects($this->never())
            ->method('add');

        $discord->expects($this->never())
            ->method('getChannel');;

        $notifyDiscordClips = new NotifyDiscordClips(
            $logger,
            $discord,
            $twitchWrapper,
            $guildSettingsRepository,
            $streamerRepository,
            $this->getEmbedQuery(),
            $notifiedClipsRepository
        );

        $notifyDiscordClips->execute();
    }

    private function getGuildSettings(string $id): GuildSettings
    {
        $guildSettings = new GuildSettings($id);
        $guildSettings->addClipChannel('streamer-' . $id, 'channel-' . $id);

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

    private function getClip(string $id): TwitchClip
    {
        $id = 'SomeRandomWordIdentifier' . $id;
        return TwitchClip::fromJsonArray([
            "id" => $id,
            "url" => 'https://clips.twitch.tv/' . $id,
            "embed_url" => 'https://clips.twitch.tv/embed?clip=' . $id,
            "broadcaster_id" => '123',
            "broadcaster_name" => 'streamer',
            "creator_id" => '456',
            "creator_name" => 'creator',
            "video_id" => '',
            "game_id" => '19030',
            "language" => 'en',
            "title" => 'Clip Title',
            "view_count" => 123,
            "created_at" => '2024-08-20T19:12:14Z',
            "thumbnail_url" => 'https://example.com/1920x1080.jpg',
            "duration" => 12.1,
            "vod_offset" => null,
            "is_featured" => false,
        ]);
    }

    private function getEmbedQuery(): DiscordClipEmbedQuery
    {
        $discord    = $this->createPartialMock(Discord::class, ['__construct', 'getFactory', 'getHttpClient']);
        $httpClient = $this->createMock(\Discord\Http\Http::class);
        $factory    = new \Discord\Factory\Factory($discord, $httpClient);
        $gameRepo   = $this->createMock(GameRepository::class);

        $gameRepo->expects($this->any())
            ->method('get')
            ->with('19030')
            ->willReturn('Game Name');

        $discord->method('getFactory')->willReturn($factory);
        $discord->method('getHttpClient')->willReturn($httpClient);

        $query = new DiscordClipEmbedQuery($discord, $gameRepo);

        return $query;
    }
}
