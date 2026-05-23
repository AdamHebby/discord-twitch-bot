<?php

namespace DiscordTwitchBot\Model;

use DateTimeImmutable;
use DateTimeZone;

class Stream
{
    /**
     * @param array<string> $tags
     * @param array<int> $tagIds
     */
    public function __construct(
        public readonly string $id,
        public readonly string $userId,
        public readonly string $userLogin,
        public readonly string $userName,
        public readonly string $gameId,
        public readonly string $gameName,
        public readonly string $type,
        public readonly string $title,
        public readonly array $tags,
        public readonly int $viewerCount,
        public readonly DateTimeImmutable $startedAt,
        public readonly string $language,
        public readonly string $thumbnailUrl,
        public readonly array $tagIds,
        public readonly bool $isMature,
    ) {
    }

    public function getEscapedUsername(): string
    {
        return str_replace("_", "\\_", $this->userName);
    }

    public function getEscapedUserLogin(): string
    {
        return str_replace("_", "\\_", $this->userLogin);
    }

    public function getThumbnailUrl(int $width = 1920, int $height = 1080): string
    {
        return str_replace(
            ['{width}', '{height}'],
            [$width, $height],
            $this->thumbnailUrl
        ) . "?v=" . time();
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJsonArray(array $data): self
    {
        $utc = new DateTimeZone("UTC");
        $defaultNow = new DateTimeImmutable("now", $utc);

        return new self(
            $data["id"],
            $data["user_id"],
            $data["user_login"],
            $data["user_name"],
            $data["game_id"],
            $data["game_name"],
            $data["type"],
            $data["title"],
            $data["tags"] ?? [],
            $data["viewer_count"],
            DateTimeImmutable::createFromFormat(
                DateTimeImmutable::RFC3339,
                $data["started_at"],
                $utc
            ) ?: $defaultNow,
            $data["language"],
            $data["thumbnail_url"],
            $data["tag_ids"] ?? [],
            $data["is_mature"],
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'user_login' => $this->userLogin,
            'user_name' => $this->userName,
            'game_id' => $this->gameId,
            'game_name' => $this->gameName,
            'type' => $this->type,
            'title' => $this->title,
            'tags' => $this->tags ?? [],
            'viewer_count' => $this->viewerCount,
            'started_at' => $this->startedAt->format(DateTimeImmutable::RFC3339),
            'language' => $this->language,
            'thumbnail_url' => $this->thumbnailUrl,
            'tag_ids' => $this->tagIds ?? [],
            'is_mature' => $this->isMature,
        ];
    }
}
