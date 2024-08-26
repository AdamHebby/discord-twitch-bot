<?php

namespace DiscordTwitchBot\Test\Repository;

use DiscordTwitchBot\Repository\GameRepository;
use DiscordTwitchBot\Test\Repository\RepositoryTestCase;

class GameRepositoryTest extends RepositoryTestCase
{
    private $databaseFile = self::TEST_DIR . '/games.db';

    public function testFileAndTableCreation(): void
    {
        $repository = new GameRepository($this->databaseFile);

        $this->assertFileExists($this->databaseFile);
        $pdo = $this->getPdo($this->databaseFile);

        $tables = $pdo->query(
            'SELECT count(*) FROM sqlite_master WHERE type="table" AND name="games"'
        )->fetchColumn();

        $this->assertEquals(1, $tables);
    }

    public function testAddRemove(): void
    {
        $repository = new GameRepository($this->databaseFile);
        $repository->add('game1', 'Game 1');

        $this->assertSame('Game 1', $repository->get('game1'));

        $repository->remove('game1');

        $this->assertNull($repository->get('game1'));
    }
}
