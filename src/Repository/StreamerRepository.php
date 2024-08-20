<?php

namespace DiscordTwitchBot\Repository;

use DiscordTwitchBot\Model\Streamer;

class StreamerRepository extends SqlLiteDatabaseRepository
{
    public function __construct(string $path)
    {
        parent::__construct($path);

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS streamer (
            `id` TEXT NOT NULL,
            `login` TEXT NOT NULL,
            `display_name` TEXT NOT NULL,
            `type` TEXT NOT NULL,
            `broadcaster_type` TEXT NOT NULL,
            `description` TEXT NOT NULL,
            `profile_image_url` TEXT NOT NULL,
            `offline_image_url` TEXT NOT NULL,
            `view_count` INTEGER NOT NULL,
            `created_at` TEXT NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE (`login`)
        )');
    }

    public function add(Streamer $streamer): void
    {
        $stmt = $this->pdo->prepare(<<<SQL
            INSERT INTO streamer (
                `id`,
                `login`,
                `display_name`,
                `type`,
                `broadcaster_type`,
                `description`,
                `profile_image_url`,
                `offline_image_url`,
                `view_count`,
                `created_at`
            ) VALUES (
                :id,
                :login,
                :display_name,
                :type,
                :broadcaster_type,
                :description,
                :profile_image_url,
                :offline_image_url,
                :view_count,
                :created_at
            ) ON CONFLICT(`login`) DO UPDATE SET
                `id` = :id,
                `display_name` = :display_name,
                `type` = :type,
                `broadcaster_type` = :broadcaster_type,
                `description` = :description,
                `profile_image_url` = :profile_image_url,
                `offline_image_url` = :offline_image_url,
                `view_count` = :view_count,
                `created_at` = :created_at
        SQL);

        $stmt->execute($streamer->toArray());
    }

    public function removeByLogin(string $login): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM streamer WHERE `login` = :login');
        $stmt->execute(['login' => $login]);
    }

    public function removeById(string $streamerId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM streamer WHERE `id` = :id');
        $stmt->execute(['id' => $streamerId]);
    }

    /**
     * @return Streamer[]
     */
    public function getAll(): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM streamer');
        $stmt->execute();

        return array_map(fn($row) => Streamer::fromJsonArray($row), $stmt->fetchAll());
    }

    public function hasStreamer(string $login): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM streamer WHERE `login` = :login');
        $stmt->execute(['login' => $login]);

        return $stmt->fetchColumn() > 0;
    }

    public function hasStreamerById(string $streamerId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM streamer WHERE `id` = :id');
        $stmt->execute(['id' => $streamerId]);

        return $stmt->fetchColumn() > 0;
    }

    public function getStreamerByLogin(string $login): ?Streamer
    {
        $stmt = $this->pdo->prepare('SELECT * FROM streamer WHERE `login` = :login');
        $stmt->execute(['login' => $login]);

        $data = $stmt->fetch();
        if ($data === false) {
            return null;
        }

        return Streamer::fromJsonArray($data);
    }

    public function getStreamerById(string $streamerId): ?Streamer
    {
        $stmt = $this->pdo->prepare('SELECT * FROM streamer WHERE `id` = :`id`');
        $stmt->execute(['id' => $streamerId]);

        $data = $stmt->fetch();
        if ($data === false) {
            return null;
        }

        return Streamer::fromJsonArray($data);
    }
}
