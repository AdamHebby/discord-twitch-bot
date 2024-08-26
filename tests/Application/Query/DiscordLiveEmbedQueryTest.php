<?php

namespace DiscordTwitchBot\Test\Application\Query;

use Discord\Discord;
use DiscordTwitchBot\Application\EmbeddedMessage;
use DiscordTwitchBot\Application\Query\DiscordLiveEmbedQuery;
use DiscordTwitchBot\Model\GuildSettings;
use DiscordTwitchBot\Model\Stream;
use DiscordTwitchBot\Model\Streamer;
use PHPUnit\Framework\TestCase;

class DiscordLiveEmbedQueryTest extends TestCase
{
    public function testExecuteReturnsCorrectEmbed(): void
    {
        $discord    = $this->createPartialMock(Discord::class, ['__construct', 'getFactory', 'getHttpClient']);
        $httpClient = $this->createMock(\Discord\Http\Http::class);
        $factory    = new \Discord\Factory\Factory($discord, $httpClient);

        $discord->method('getFactory')->willReturn($factory);
        $discord->method('getHttpClient')->willReturn($httpClient);

        $query = new DiscordLiveEmbedQuery($discord);

        $guildSettings = new GuildSettings('123');
        $guildSettings->addStreamer('user_login', null, 'channel');
        $streamerData  = [
            'id' => '123',
            'login' => 'user',
            'display_name' => 'User',
            'type' => 'unknown',
            'broadcaster_type' => 'affiliate',
            'description' => 'Test',
            'profile_image_url' => 'https://example.com/profile.jpg',
            'offline_image_url' => 'https://example.com/offline.jpg',
            'view_count' => 100,
            'created_at' => '2024-01-01T00:00:00+00:00',
        ];

        $streamData = [
            "id" => "123",
            "user_id" => "456",
            "user_login" => "user_login",
            "user_name" => "user_name",
            "game_id" => "789",
            "game_name" => "game_name",
            "type" => "live",
            "title" => "title",
            "tags" => [],
            "viewer_count" => 100,
            "started_at" => "2024-01-01T00:00:00Z",
            "language" => "en",
            "thumbnail_url" => "https://example.com/{width}x{height}.jpg",
            "tag_ids" => [],
            "is_mature" => false,
        ];

        $stream   = Stream::fromJsonArray($streamData);
        $streamer = Streamer::fromJsonArray($streamerData);
        $embed    = $query->generate($guildSettings, $streamer, $stream);

        $this->assertInstanceOf(EmbeddedMessage::class, $embed);

        $expectedMessage = DiscordLiveEmbedQuery::DEFAULT_MSG;
        $expectedMessage = str_replace(
            ['{name}', '{game}', '{ name }', '{ game }'],
            ['user\_name', 'game_name', 'user\_name', 'game_name'],
            $expectedMessage
        );

        $this->assertSame($expectedMessage, $embed->message);
        $this->assertStringStartsWith(
            'https://www.twitch.tv/user_login',
            $embed->embed->url
        );
    }
}