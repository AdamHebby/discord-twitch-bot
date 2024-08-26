<?php

namespace DiscordTwitchBot\Test\Application\Query;

use Discord\Discord;
use DiscordTwitchBot\Application\EmbeddedMessage;
use DiscordTwitchBot\Application\Query\DiscordClipEmbedQuery;
use DiscordTwitchBot\Model\GuildSettings;
use DiscordTwitchBot\Model\TwitchClip;
use DiscordTwitchBot\Repository\GameRepository;
use PHPUnit\Framework\TestCase;

class DiscordClipEmbedQueryTest extends TestCase
{
    public function testExecuteReturnsCorrectEmbed(): void
    {
        $discord    = $this->createPartialMock(Discord::class, ['__construct', 'getFactory', 'getHttpClient']);
        $httpClient = $this->createMock(\Discord\Http\Http::class);
        $factory    = new \Discord\Factory\Factory($discord, $httpClient);
        $gameRepo   = $this->createMock(GameRepository::class);

        $gameRepo->expects($this->once())
            ->method('get')
            ->with('19030')
            ->willReturn('Game Name');

        $discord->method('getFactory')->willReturn($factory);
        $discord->method('getHttpClient')->willReturn($httpClient);

        $query = new DiscordClipEmbedQuery($discord, $gameRepo);

        $guildSettings = new GuildSettings('123');
        $clip = TwitchClip::fromJsonArray([
            "id" => 'SomeRandomWordIdentifier',
            "url" => 'https://clips.twitch.tv/SomeRandomWordIdentifier',
            "embed_url" => 'https://clips.twitch.tv/embed?clip=SomeRandomWordIdentifier',
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

        $embed = $query->generate($clip);

        $this->assertInstanceOf(EmbeddedMessage::class, $embed);
        $this->assertSame("", $embed->message);
        $this->assertSame("Clip Title", $embed->embed->title);
        $this->assertSame("https://clips.twitch.tv/SomeRandomWordIdentifier", $embed->embed->url);
        $this->assertSame("https://example.com/1920x1080.jpg", $embed->embed->image['url']);
        $this->assertSame("Game Name", $embed->embed->fields["Game"]['value']);
    }

    public function testExecuteOmitsGameNameWhenNotKnown(): void
    {
        $discord    = $this->createPartialMock(Discord::class, ['__construct', 'getFactory', 'getHttpClient']);
        $httpClient = $this->createMock(\Discord\Http\Http::class);
        $factory    = new \Discord\Factory\Factory($discord, $httpClient);
        $gameRepo   = $this->createMock(GameRepository::class);

        $gameRepo->expects($this->once())
            ->method('get')
            ->with('19030')
            ->willReturn(null);

        $discord->method('getFactory')->willReturn($factory);
        $discord->method('getHttpClient')->willReturn($httpClient);

        $query = new DiscordClipEmbedQuery($discord, $gameRepo);

        $clip = TwitchClip::fromJsonArray([
            "id" => 'SomeRandomWordIdentifier',
            "url" => 'https://clips.twitch.tv/SomeRandomWordIdentifier',
            "embed_url" => 'https://clips.twitch.tv/embed?clip=SomeRandomWordIdentifier',
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

        $embed = $query->generate($clip);

        $this->assertInstanceOf(EmbeddedMessage::class, $embed);
        $this->assertSame("", $embed->message);
        $this->assertSame("Clip Title", $embed->embed->title);
        $this->assertSame("https://clips.twitch.tv/SomeRandomWordIdentifier", $embed->embed->url);
        $this->assertSame("https://example.com/1920x1080.jpg", $embed->embed->image['url']);
        $this->assertArrayNotHasKey("Game", $embed->embed->fields);
    }
}
