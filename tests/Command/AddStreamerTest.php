<?php

namespace DiscordTwitchBot\Test\Command;

use Discord\Builders\MessageBuilder;
use Discord\Parts\Channel\Channel;
use Discord\Repository\Guild\ChannelRepository;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use PHPUnit\Framework\TestCase;
use DiscordTwitchBot\Command\AddStreamer;
use DiscordTwitchBot\Model\GuildSettings;
use DiscordTwitchBot\Model\Streamer;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use DiscordTwitchBot\Repository\StreamerRepository;
use DiscordTwitchBot\Test\DiscordMocker;
use Psr\Log\NullLogger;

use function React\Promise\resolve;

class AddStreamerTest extends TestCase
{
    use DiscordMocker;

    public function testExecutesCorrectly(): void
    {
        $twitchWrapper           = $this->createMock(TwitchWrapper::class);
        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);
        $streamerRepository      = $this->createMock(StreamerRepository::class);

        $guildSettings = new GuildSettings('123');
        $guildSettingsRepository
            ->method('load')
            ->with('123')
            ->willReturn($guildSettings);

        $addStreamer = new AddStreamer(
            new NullLogger(),
            $twitchWrapper,
            $guildSettingsRepository,
            $streamerRepository,
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
                    'custom_message' => (object) ['value' => 'test message'],
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
                return $guildSettings->hasStreamer('teststreamer');
            }));

        $interaction->expects($this->once())->method('acknowledgeWithResponse')->willReturn(resolve(true));

        $interaction->expects($this->once())
            ->method('updateOriginalResponse')
            ->with($this->callback(function (MessageBuilder $message) {
                $data = json_decode(json_encode($message), true);
                return $data['content'] === 'teststreamer is now being watched!';
            }));

        $addStreamer->execute($interaction);
    }

    public function testExecuteChannelNotFoundError(): void
    {
        $twitchWrapper           = $this->createMock(TwitchWrapper::class);
        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);
        $streamerRepository      = $this->createMock(StreamerRepository::class);

        $guildSettings = new GuildSettings('123');
        $guildSettingsRepository
            ->method('load')
            ->with('123')
            ->willReturn($guildSettings);

        $addStreamer = new AddStreamer(
            new NullLogger(),
            $twitchWrapper,
            $guildSettingsRepository,
            $streamerRepository,
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
            ['data', (object) [
                'options' => [
                    'streamer' => (object) ['value' => 'teststreamer'],
                    'channel' => (object) ['value' => 'channel_id_here'],
                    'custom_message' => (object) ['value' => 'test message'],
                ]
            ]],
        ]);

        $interaction->expects($this->once())->method('acknowledgeWithResponse')->willReturn(resolve(true));

        $interaction->expects($this->once())
            ->method('updateOriginalResponse')
            ->with($this->callback(function (MessageBuilder $message) {
                $data = json_decode(json_encode($message), true);
                return $data['content'] === "I can't see the channel <#channel_id_here>.";
            }));

        $addStreamer->execute($interaction);
    }

    public function testExecuteAlreadyBeingWatchedDoesntUpdate(): void
    {

        $twitchWrapper           = $this->createMock(TwitchWrapper::class);
        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);
        $streamerRepository      = $this->createMock(StreamerRepository::class);

        $guildSettings = new GuildSettings('123');
        $guildSettings->addStreamer('teststreamer', 'test message', 'channel_id_here');
        $guildSettingsRepository
            ->method('load')
            ->with('123')
            ->willReturn($guildSettings);

        $addStreamer = new AddStreamer(
            new NullLogger(),
            $twitchWrapper,
            $guildSettingsRepository,
            $streamerRepository,
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
                    'custom_message' => (object) ['value' => 'test message'],
                ]
            ]],
        ]);

        $interaction->expects($this->once())->method('acknowledgeWithResponse')->willReturn(resolve(true));

        $interaction->expects($this->once())
            ->method('updateOriginalResponse')
            ->with($this->callback(function (MessageBuilder $message) {
                $data = json_decode(json_encode($message), true);
                return $data['content'] === 'teststreamer is already being watched in that channel with that message!';
            }));

        $addStreamer->execute($interaction);
    }

    public function testExecuteAlreadyBeingWatchedUpdates(): void
    {
        $twitchWrapper           = $this->createMock(TwitchWrapper::class);
        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);
        $streamerRepository      = $this->createMock(StreamerRepository::class);

        $guildSettings = new GuildSettings('123');
        $guildSettings->addStreamer('teststreamer', 'test message', 'channel_id_here');
        $guildSettingsRepository
            ->method('load')
            ->with('123')
            ->willReturn($guildSettings);

        $addStreamer = new AddStreamer(
            new NullLogger(),
            $twitchWrapper,
            $guildSettingsRepository,
            $streamerRepository,
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
                    'custom_message' => (object) ['value' => null],
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
                return $guildSettings->hasStreamer('teststreamer');
            }));

        $interaction->expects($this->once())->method('acknowledgeWithResponse')->willReturn(resolve(true));

        $interaction->expects($this->once())
            ->method('updateOriginalResponse')
            ->with($this->callback(function (MessageBuilder $message) {
                $data = json_decode(json_encode($message), true);
                return $data['content'] === "teststreamer is already being watched, but I've updated the channel and message!";
            }));

        $addStreamer->execute($interaction);
    }

    public function testExecuteNotAValidStreamerError(): void
    {
        $twitchWrapper           = $this->createMock(TwitchWrapper::class);
        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);
        $streamerRepository      = $this->createMock(StreamerRepository::class);

        $guildSettings = new GuildSettings('123');
        $guildSettingsRepository
            ->method('load')
            ->with('123')
            ->willReturn($guildSettings);

        $addStreamer = new AddStreamer(
            new NullLogger(),
            $twitchWrapper,
            $guildSettingsRepository,
            $streamerRepository,
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
                    'custom_message' => (object) ['value' => 'test message'],
                ]
            ]],
        ]);

        $twitchWrapper
            ->method('getStreamer')
            ->with('teststreamer')
            ->willReturn(null);

        $streamerRepository
            ->expects($this->never())
            ->method('add');

        $interaction->expects($this->once())->method('acknowledgeWithResponse')->willReturn(resolve(true));

        $interaction->expects($this->once())
            ->method('updateOriginalResponse')
            ->with($this->callback(function (MessageBuilder $message) {
                $data = json_decode(json_encode($message), true);
                return $data['content'] === 'teststreamer is not a valid Twitch streamer!';
            }));

        $addStreamer->execute($interaction);
    }
}
