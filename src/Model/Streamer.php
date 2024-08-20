<?php

namespace DiscordTwitchBot\Model;

use DateTimeImmutable;
use DateTimeZone;

class Streamer
{
    public function __construct(
        public readonly string $id,
        public readonly string $login,
        public readonly string $displayName,
        public readonly string $type,
        public readonly string $broadcasterType,
        public readonly string $description,
        public readonly string $profileImageUrl,
        public readonly string $offlineImageUrl,
        public readonly int $viewCount,
        public readonly DateTimeImmutable $createdAt,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromJsonArray(array $data): self
    {
        $utc = new DateTimeZone("UTC");
        return new self(
            $data["id"],
            $data["login"],
            $data["display_name"],
            $data["type"],
            $data["broadcaster_type"],
            $data["description"],
            $data["profile_image_url"],
            $data["offline_image_url"],
            $data["view_count"],
            DateTimeImmutable::createFromFormat(DateTimeImmutable::RFC3339, $data["created_at"], $utc),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'login' => $this->login,
            'display_name' => $this->displayName,
            'type' => $this->type,
            'broadcaster_type' => $this->broadcasterType,
            'description' => $this->description,
            'profile_image_url' => $this->profileImageUrl,
            'offline_image_url' => $this->offlineImageUrl,
            'view_count' => $this->viewCount,
            'created_at' => $this->createdAt->format(DateTimeImmutable::RFC3339),
        ];
    }
}
