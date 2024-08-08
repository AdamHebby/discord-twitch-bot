<?php

namespace DiscordTwitchBot\Repository;

abstract class SqlLiteDatabaseRepository
{
    protected \PDO $pdo;

    public function __construct(
        private string $path
    ) {
        $this->pdo = new \PDO("sqlite:{$path}");
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }
}
