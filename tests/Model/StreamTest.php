<?php

namespace DiscordTwitchBot\Test\Model;

use PHPUnit\Framework\TestCase;
use DiscordTwitchBot\Model\Stream;

class StreamTest extends TestCase
{
    public function testStream(): void
    {
        $data = [
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
            "started_at" => "2024-01-01T00:00:00+00:00",
            "language" => "en",
            "thumbnail_url" => "https://example.com/{width}x{height}.jpg",
            "tag_ids" => [],
            "is_mature" => false,
        ];
        $stream = Stream::fromJsonArray($data);

        $this->assertSame("123", $stream->id);
        $this->assertSame(
            "https://example.com/1920x1080.jpg",
            $stream->getThumbnailUrl()
        );
        $this->assertSame(
            "https://example.com/123x456.jpg",
            $stream->getThumbnailUrl(123, 456)
        );

        $this->assertSame($data, $stream->toArray());

        $this->assertSame("user\_login", $stream->getEscapedUserLogin());
        $this->assertSame("user\_name", $stream->getEscapedUsername());
    }
}
