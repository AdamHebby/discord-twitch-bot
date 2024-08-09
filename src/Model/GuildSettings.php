<?php

namespace DiscordTwitchBot\Model;

class GuildSettings
{
    public const DEFAULT_PREFIX = '!sb';

    public function __construct(
        private string $guildId,
        private array $streamers = [],
        private string $prefix = self::DEFAULT_PREFIX,
        private string $channel = 'announcements',
        private ?string $role = null,
    ) {
    }

    public function getGuildId(): string
    {
        return $this->guildId;
    }

    public function getStreamers(): array
    {
        return array_keys($this->streamers);
    }

    public function getStreamerMessage(string $streamer): ?string
    {
        return $this->streamers[$streamer] ?? null;
    }

    public function addStreamer(string $streamer, ?string $message = null): void
    {
        $this->streamers[$streamer] = $message;
    }

    public function hasStreamer(string $streamer): bool
    {
        return isset($this->streamers[$streamer]);
    }

    public function removeStreamer(string $streamer): void
    {
        unset($this->streamers[$streamer]);
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    public function setChannel(string $channel): void
    {
        $this->channel = $channel;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function setRole(?string $role): void
    {
        $this->role = $role;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public static function fromJsonArray(array $data): self
    {
        return new self(
            $data['guildId'],
            $data['streamers'] ?? [],
            $data['prefix'] ?? self::DEFAULT_PREFIX,
            $data['channel'] ?? 'announcements',
            $data['role'] ?? null,
        );
    }

    public function toJsonArray(): array
    {
        return [
            'guildId' => $this->guildId,
            'streamers' => $this->streamers,
            'prefix' => $this->prefix,
            'channel' => $this->channel,
            'role' => $this->role,
        ];
    }
}