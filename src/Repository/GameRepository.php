<?php

namespace DiscordTwitchBot\Repository;

class GameRepository extends SqlLiteDatabaseRepository
{
    public function __construct(string $path)
    {
        parent::__construct($path);

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS games (
            game_id TEXT,
            game_name TEXT,
            PRIMARY KEY (game_id)
        )');
    }

    public function add(string $gameId, string $gameName): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO games (game_id, game_name)
            VALUES (:game_id, :game_name)
            ON CONFLICT(game_id) DO UPDATE SET game_name = :game_name'
        );

        $stmt->execute([
            'game_id'  => $gameId,
            'game_name' => $gameName,
        ]);
    }

    public function remove(string $gameId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM games WHERE game_id = :game_id');

        $stmt->execute([
            'game_id'  => $gameId,
        ]);
    }

    public function get(string $gameId): ?string
    {
        $stmt = $this->pdo->prepare(
            'SELECT game_name FROM games WHERE game_id = :game_id'
        );

        $stmt->execute([
            'game_id'  => $gameId,
        ]);

        return $stmt->fetchColumn();
    }
}
