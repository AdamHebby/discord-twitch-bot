<?php

namespace DiscordTwitchBot\Model;

use DateTimeImmutable;
use DateTimeZone;

class TwitchClip
{
    public function __construct(
        public readonly string $id,
        // public readonly string $url,
        // public readonly string $embedUrl,
        public readonly string $broadcasterId,
        public readonly string $broadcasterName,
        public readonly string $creatorId,
        public readonly string $creatorName,
        public readonly string $videoId,
        public readonly string $gameId,
        public readonly string $language,
        public readonly string $title,
        public readonly int $viewCount,
        public readonly DateTimeImmutable $createdAt,
        public readonly string $thumbnailUrl,
        public readonly int|float $duration,
        public readonly ?int $vodOffset,
        public readonly bool $isFeatured,
    ) {
    }

    public static function fromJsonArray(array $jsonData): self
    {
        $utc = new DateTimeZone("UTC");
        $defaultNow = new DateTimeImmutable("now", $utc);

        return new self(
            $jsonData["id"],
            // $jsonData["url"],
            // $jsonData["embed_url"],
            $jsonData["broadcaster_id"],
            $jsonData["broadcaster_name"],
            $jsonData["creator_id"],
            $jsonData["creator_name"],
            $jsonData["video_id"],
            $jsonData["game_id"],
            $jsonData["language"],
            $jsonData["title"],
            $jsonData["view_count"],
            DateTimeImmutable::createFromFormat(
                DateTimeImmutable::RFC3339,
                $jsonData["created_at"],
                $utc
            ) ?: $defaultNow,
            $jsonData["thumbnail_url"],
            $jsonData["duration"],
            $jsonData["vod_offset"],
            $jsonData["is_featured"],
        );
    }

    public function getUrl(): string
    {
        return "https://clips.twitch.tv/{$this->id}";
    }

    public function getEmbedUrl(): string
    {
        return "https://clips.twitch.tv/embed?clip={$this->id}";
    }
}
