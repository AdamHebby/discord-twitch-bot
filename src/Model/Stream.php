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
        private readonly string $id,
        private readonly string $userId,
        private readonly string $userLogin,
        private readonly string $userName,
        private readonly string $gameId,
        private readonly string $gameName,
        private readonly string $type,
        private readonly string $title,
        private readonly array $tags,
        private readonly int $viewerCount,
        private readonly DateTimeImmutable $startedAt,
        private readonly string $language,
        private readonly string $thumbnailUrl,
        private readonly array $tagIds,
        private readonly bool $isMature,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getUserLogin(): string
    {
        return $this->userLogin;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getEscapedUsername(): string
    {
        return str_replace("_", "\\_", $this->userName);
    }

    public function getEscapedUserLogin(): string
    {
        return str_replace("_", "\\_", $this->userLogin);
    }

    public function getGameId(): string
    {
        return $this->gameId;
    }

    public function getGameName(): string
    {
        return $this->gameName;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return array<string>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getViewerCount(): int
    {
        return $this->viewerCount;
    }

    public function getStartedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getThumbnailUrl(int $width = 1920, int $height = 1080): string
    {
        return str_replace(['{width}', '{height}'], [$width, $height], $this->thumbnailUrl);
    }

    /**
     * @return array<int>
     */
    public function getTagIds(): array
    {
        return $this->tagIds;
    }

    public function isMature(): bool
    {
        return $this->isMature;
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJsonArray(array $data): self
    {
        $utc = new DateTimeZone("UTC");
        return new self(
            $data["id"],
            $data["user_id"],
            $data["user_login"],
            $data["user_name"],
            $data["game_id"],
            $data["game_name"],
            $data["type"],
            $data["title"],
            $data["tags"],
            $data["viewer_count"],
            DateTimeImmutable::createFromFormat(DateTimeImmutable::RFC3339, $data["started_at"], $utc),
            $data["language"],
            $data["thumbnail_url"],
            $data["tag_ids"],
            $data["is_mature"],
        );
    }
}
