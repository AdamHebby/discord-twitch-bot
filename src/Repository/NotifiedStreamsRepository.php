<?php

namespace DiscordTwitchBot\Repository;

class NotifiedStreamsRepository extends SqlLiteDatabaseRepository
{
    public function __construct()
    {
        parent::__construct(__DIR__ . '/../../data/notified_streams.db');

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS notified_streams (
            stream_id TEXT,
            guild_id TEXT,
            streamer_name TEXT,
            notified_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (stream_id, guild_id)
        )');
    }

    public function add(string $streamerName, string $streamId, string $guildId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO notified_streams (stream_id, guild_id, streamer_name) ' .
            'VALUES (:stream_id, :guild_id, :streamer_name)'
        );
        $stmt->execute([
            'stream_id' => $streamId,
            'guild_id'  => $guildId,
            'streamer_name' => $streamerName,
        ]);
    }

    public function remove(string $streamId, string $guildId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM notified_streams WHERE stream_id = :stream_id AND guild_id = :guild_id'
        );

        $stmt->execute([
            'stream_id' => $streamId,
            'guild_id'  => $guildId,
        ]);
    }

    public function isNotified(string $streamId, string $guildId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM notified_streams WHERE stream_id = :stream_id AND guild_id = :guild_id'
        );

        $stmt->execute([
            'stream_id' => $streamId,
            'guild_id'  => $guildId,
        ]);

        return $stmt->fetchColumn() > 0;
    }

    public function isNotifiedRestartedStream(string $streamerName, string $guildId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM notified_streams WHERE ' .
            'streamer_name = :streamer_name AND guild_id = :guild_id AND notified_at >= datetime("now", "-20 minutes")'
        );

        $stmt->execute([
            'streamer_name' => $streamerName,
            'guild_id'      => $guildId,
        ]);

        return $stmt->fetchColumn() > 0;
    }
}
