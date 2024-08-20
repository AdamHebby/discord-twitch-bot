<?php

namespace DiscordTwitchBot\Repository;

use PDO;

abstract class SqlLiteDatabaseRepository implements RepositoryInterface
{
    protected PDO $pdo;

    public function __construct(
        private readonly string $path
    ) {
        $this->pdo = new PDO("sqlite:{$this->path}");
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
}
