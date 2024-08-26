<?php

namespace DiscordTwitchBot\Test\Command;

use PHPUnit\Framework\TestCase;
use DiscordTwitchBot\Command\WatchClips;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Channel\Channel;
use Discord\Repository\Guild\ChannelRepository;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Model\GuildSettings;
use DiscordTwitchBot\Model\Streamer;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use DiscordTwitchBot\Repository\StreamerRepository;
use DiscordTwitchBot\Test\DiscordMocker;
use Psr\Log\NullLogger;

class WatchClipsTest extends TestCase
{
    use DiscordMocker;

    public function testExecuteAddsWatchedClipChannel(): void
    {
        $twitchWrapper           = $this->createMock(TwitchWrapper::class);
        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);
        $streamerRepository      = $this->createMock(StreamerRepository::class);

        $guildSettings = new GuildSettings('123');
        $guildSettingsRepository
            ->method('load')
            ->with('123')
            ->willReturn($guildSettings);

        $watchClips = new WatchClips(
            new NullLogger(),
            $twitchWrapper,
            $streamerRepository,
            $guildSettingsRepository,
        );

        $channelRepo = $this->createMock(ChannelRepository::class);
        $channel = $this->createMock(Channel::class);
        $channel->method('__get')
            ->with('id')
            ->willReturn('channel_id_here');

        $channelRepo
            ->method('get')
            ->with('id', 'channel_id_here')
            ->willReturn($channel);

        $interaction = $this->mockInteraction([
            ['guild', $this->mockGuild([
                ['name', 'testguild'],
                ['channels', $channelRepo],
            ])],
            ['guild_id', '123'],
            ['member', $this->mockMember([
                ['username', 'testuser'],
                ['id', '123'],
            ])],
            ['data', (object) [
                'options' => [
                    'streamer' => (object) ['value' => 'teststreamer'],
                    'channel' => (object) ['value' => 'channel_id_here'],
                ]
            ]],
        ]);

        $streamer = Streamer::fromJsonArray([
            'id' => '123',
            'login' => 'testuser',
            'display_name' => 'Test User',
            'type' => 'live',
            'broadcaster_type' => 'affiliate',
            'description' => 'Test',
            'profile_image_url' => 'https://example.com/profile.jpg',
            'offline_image_url' => 'https://example.com/offline.jpg',
            'view_count' => 100,
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $twitchWrapper
            ->method('getStreamer')
            ->with('teststreamer')
            ->willReturn($streamer);

        $streamerRepository
            ->expects($this->once())
            ->method('add')
            ->with($streamer);

        $guildSettingsRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (GuildSettings $guildSettings) {
                return $guildSettings->hasStreamer('teststreamer') === false
                    && $guildSettings->getClipChannel('teststreamer') === 'channel_id_here';
            }));

        $interaction->expects($this->once())
            ->method('respondWithMessage')
            ->with($this->callback(function (MessageBuilder $message) {
                $data = json_decode(json_encode($message), true);
                return $data['content'] === 'Clips for streamer `teststreamer` will now be posted in <#channel_id_here>.';
            }));

        $watchClips->execute($interaction);
    }

    public function testExecuteFailsWhenStreamerDoesntExist(): void
    {
        $twitchWrapper           = $this->createMock(TwitchWrapper::class);
        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);
        $streamerRepository      = $this->createMock(StreamerRepository::class);

        $streamerRepository->expects($this->once())
            ->method('getStreamerByLogin')
            ->with('teststreamer')
            ->willReturn(null);

        $twitchWrapper
            ->method('getStreamer')
            ->with('teststreamer')
            ->willReturn(null);

        $guildSettings = new GuildSettings('123');
        $guildSettingsRepository
            ->method('load')
            ->with('123')
            ->willReturn($guildSettings);

        $watchClips = new WatchClips(
            new NullLogger(),
            $twitchWrapper,
            $streamerRepository,
            $guildSettingsRepository,
        );

        $interaction = $this->mockInteraction([
            ['guild', $this->mockGuild([
                ['name', 'testguild'],
            ])],
            ['guild_id', '123'],
            ['member', $this->mockMember([
                ['username', 'testuser'],
                ['id', '123'],
            ])],
            ['data', (object) [
                'options' => [
                    'streamer' => (object) ['value' => 'teststreamer'],
                    'channel' => (object) ['value' => 'channel_id_here'],
                ]
            ]],
        ]);

        $streamerRepository
            ->expects($this->never())
            ->method('add');

        $interaction->expects($this->once())
            ->method('respondWithMessage')
            ->with($this->callback(function (MessageBuilder $message) {
                $data = json_decode(json_encode($message), true);
                return $data['content'] === 'Streamer `teststreamer` does not exist.';
            }));

        $watchClips->execute($interaction);
    }

    public function testExecuteFailsWhenChannelDoesntExist(): void
    {
        $twitchWrapper           = $this->createMock(TwitchWrapper::class);
        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);
        $streamerRepository      = $this->createMock(StreamerRepository::class);

        $guildSettings = new GuildSettings('123');
        $guildSettingsRepository
            ->method('load')
            ->with('123')
            ->willReturn($guildSettings);

        $watchClips = new WatchClips(
            new NullLogger(),
            $twitchWrapper,
            $streamerRepository,
            $guildSettingsRepository,
        );

        $channelRepo = $this->createMock(ChannelRepository::class);
        $channelRepo
            ->method('get')
            ->with('id', 'channel_id_here')
            ->willReturn(null);

        $interaction = $this->mockInteraction([
            ['guild', $this->mockGuild([
                ['name', 'testguild'],
                ['channels', $channelRepo],
            ])],
            ['guild_id', '123'],
            ['member', $this->mockMember([
                ['username', 'testuser'],
                ['id', '123'],
            ])],
            ['data', (object) [
                'options' => [
                    'streamer' => (object) ['value' => 'teststreamer'],
                    'channel' => (object) ['value' => 'channel_id_here'],
                ]
            ]],
        ]);

        $streamer = Streamer::fromJsonArray([
            'id' => '123',
            'login' => 'testuser',
            'display_name' => 'Test User',
            'type' => 'live',
            'broadcaster_type' => 'affiliate',
            'description' => 'Test',
            'profile_image_url' => 'https://example.com/profile.jpg',
            'offline_image_url' => 'https://example.com/offline.jpg',
            'view_count' => 100,
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $twitchWrapper
            ->method('getStreamer')
            ->with('teststreamer')
            ->willReturn($streamer);

        $streamerRepository
            ->expects($this->once())
            ->method('add')
            ->with($streamer);

        $interaction->expects($this->once())
            ->method('respondWithMessage')
            ->with($this->callback(function (MessageBuilder $message) {
                $data = json_decode(json_encode($message), true);
                return $data['content'] === 'Channel <#channel_id_here> not found.';
            }));

        $watchClips->execute($interaction);
    }

    public function testExecuteDetectsDuplicateWatchedClipChannel(): void
    {
        $twitchWrapper           = $this->createMock(TwitchWrapper::class);
        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);
        $streamerRepository      = $this->createMock(StreamerRepository::class);

        $guildSettings = new GuildSettings('123');
        $guildSettings->addClipChannel('teststreamer', 'channel_id_here');
        $guildSettingsRepository
            ->method('load')
            ->with('123')
            ->willReturn($guildSettings);

        $watchClips = new WatchClips(
            new NullLogger(),
            $twitchWrapper,
            $streamerRepository,
            $guildSettingsRepository,
        );

        $channelRepo = $this->createMock(ChannelRepository::class);
        $channel = $this->createMock(Channel::class);
        $channel->method('__get')
            ->with('id')
            ->willReturn('channel_id_here');

        $channelRepo
            ->method('get')
            ->with('id', 'channel_id_here')
            ->willReturn($channel);

        $interaction = $this->mockInteraction([
            ['guild', $this->mockGuild([
                ['name', 'testguild'],
                ['channels', $channelRepo],
            ])],
            ['guild_id', '123'],
            ['member', $this->mockMember([
                ['username', 'testuser'],
                ['id', '123'],
            ])],
            ['data', (object) [
                'options' => [
                    'streamer' => (object) ['value' => 'teststreamer'],
                    'channel' => (object) ['value' => 'channel_id_here'],
                ]
            ]],
        ]);

        $streamer = Streamer::fromJsonArray([
            'id' => '123',
            'login' => 'testuser',
            'display_name' => 'Test User',
            'type' => 'live',
            'broadcaster_type' => 'affiliate',
            'description' => 'Test',
            'profile_image_url' => 'https://example.com/profile.jpg',
            'offline_image_url' => 'https://example.com/offline.jpg',
            'view_count' => 100,
            'created_at' => '2024-01-01T00:00:00Z',
        ]);

        $twitchWrapper
            ->method('getStreamer')
            ->with('teststreamer')
            ->willReturn($streamer);

        $streamerRepository
            ->expects($this->once())
            ->method('add')
            ->with($streamer);

        $guildSettingsRepository
            ->expects($this->never())
            ->method('save');

        $interaction->expects($this->once())
            ->method('respondWithMessage')
            ->with($this->callback(function (MessageBuilder $message) {
                $data = json_decode(json_encode($message), true);
                return $data['content'] === 'Clips for streamer `teststreamer` are already being posted in <#channel_id_here>.';
            }));

        $watchClips->execute($interaction);
    }
}
