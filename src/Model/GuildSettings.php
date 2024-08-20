<?php

namespace DiscordTwitchBot\Model;

class GuildSettings
{
    /**
     * @param array<string,string|null> $streamers
     * @param array<string,string> $channels
     */
    public function __construct(
        private string $guildId,
        private array $streamers = [],
        private array $channels = [],
    ) {
    }

    public function getGuildId(): string
    {
        return $this->guildId;
    }

    /**
     * @return array<string>
     */
    public function getStreamers(): array
    {
        return array_keys($this->streamers);
    }

    public function getStreamerMessage(string $streamer): ?string
    {
        return $this->streamers[$streamer] ?? null;
    }

    public function addStreamer(string $streamer, ?string $message, string $channel): void
    {
        $this->streamers[$streamer] = $message;
        $this->channels[$streamer] = $channel;
    }

    public function getStreamerChannel(string $streamer): ?string
    {
        return $this->channels[$streamer] ?? null;
    }

    public function hasStreamer(string $streamer): bool
    {
        return array_key_exists($streamer, $this->streamers);
    }

    public function removeStreamer(string $streamer): void
    {
        unset($this->streamers[$streamer]);
    }

    /**
     * @return array<string,string|null>
     */
    public function getChannels(): array
    {
        return $this->channels;
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function fromJsonArray(array $data): self
    {
        return new self(
            $data['guildId'],
            $data['streamers'] ?? [],
            $data['channels'] ?? [],
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toJsonArray(): array
    {
        return [
            'guildId' => $this->guildId,
            'streamers' => $this->streamers,
            'channels' => $this->channels,
        ];
    }
}
