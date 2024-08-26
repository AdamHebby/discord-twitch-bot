<?php

namespace DiscordTwitchBot\Test\Model;

use PHPUnit\Framework\TestCase;
use DiscordTwitchBot\Model\TwitchClip;

class TwitchClipTest extends TestCase
{
    public function testTwitchClip(): void
    {
        $data = [
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
        ];
        $clip = TwitchClip::fromJsonArray($data);

        $this->assertSame("SomeRandomWordIdentifier", $clip->id);
        $this->assertSame("https://clips.twitch.tv/SomeRandomWordIdentifier", $clip->getUrl());
        $this->assertSame("https://clips.twitch.tv/embed?clip=SomeRandomWordIdentifier", $clip->getEmbedUrl());

        $this->assertSame("creator", $clip->creatorName);
    }
}
