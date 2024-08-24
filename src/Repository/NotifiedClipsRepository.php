<?php

namespace DiscordTwitchBot\Repository;

class NotifiedClipsRepository extends SqlLiteDatabaseRepository
{
    public function __construct(string $path)
    {
        parent::__construct($path);

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS notified_clips (
            clip_id TEXT,
            guild_id TEXT,
            notified_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (clip_id, guild_id)
        )');
    }

    public function add(string $clipId, string $guildId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO notified_clips (clip_id, guild_id) ' .
            'VALUES (:clip_id, :guild_id)'
        );
        $stmt->execute([
            'clip_id'  => $clipId,
            'guild_id' => $guildId,
        ]);
    }

    public function remove(string $clipId, string $guildId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM notified_clips WHERE clip_id = :clip_id AND guild_id = :guild_id'
        );

        $stmt->execute([
            'clip_id'  => $clipId,
            'guild_id' => $guildId,
        ]);
    }

    public function isNotified(string $clipId, string $guildId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM notified_clips WHERE clip_id = :clip_id AND guild_id = :guild_id'
        );

        $stmt->execute([
            'clip_id'  => $clipId,
            'guild_id' => $guildId,
        ]);

        return $stmt->fetchColumn() > 0;
    }
}
