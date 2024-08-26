<?php

namespace DiscordTwitchBot\Test\Model;

use PHPUnit\Framework\TestCase;
use DiscordTwitchBot\Model\Streamer;

class StreamerTest extends TestCase
{
    public function testStreamer(): void
    {
        $data = [
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

        $streamer = Streamer::fromJsonArray($data);

        $this->assertSame($data, $streamer->toArray());

        $this->assertSame('123', $streamer->id);

        $this->assertSame(
            'https://example.com/profile.jpg',
            $streamer->profileImageUrl
        );
    }
}
